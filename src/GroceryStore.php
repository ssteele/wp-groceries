<?php

namespace SteveSteele\Groceries;

class GroceryStore
{

    // Declare properties
    public $id;
    public $userId;
    public $db;
    public $idForGuestUse = 1;
    public $isGuest = false;


    /**
     * Construct method
     * @param integer $userId    WP user id
     * @param object  $db        Probably $wpdb, but open to new things
     *
     * @return void
     */
    public function __construct($userId = null, $db = null)
    {
        $this->setUser($userId);
        $this->setDb($db);
    }


    /**
     * Set user
     *
     * @param integer $userId    WP user id
     *
     * @return void
     */
    protected function setUser($userId = null)
    {
        if (! isset($userId)) {
            $userId = get_current_user_id();
            if (! $userId) {
                $userId = $this->idForGuestUse;
                $this->isGuest = true;
            }
        }

        $this->userId = $userId;
    }


    /**
     * Set DB
     *
     * @param object $db    Probably $wpdb, but open to new things
     *
     * @return void
     */
    protected function setDb($db = null)
    {
        if (! isset($db)) {
            // assume wpdb
            global $wpdb;
            $db = $wpdb;
        }

        $this->db = $db;
    }


    /**
     * Save new and existing user stores
     * @param int $storeId    Store identifier
     */
    private function setStore($storeId = null)
    {
        // Flag new or existing store
        $isNew = is_null($storeId) ? true : false;

        // Handle user input
        $postVars = ['name', 'number', 'street', 'city', 'zip'];

        foreach ($postVars as $p) {
            $var = ($isNew) ? $p : $p . '_' . $storeId;

            if (isset($_POST[$var]) && ! empty($_POST[$var])) {
                $$p = shsSanitize($_POST[$var], 's');
            } else {
                $$p = '';
            }
        }

        // Name field required to save store
        if (! empty($name)) {
            if ($isNew) {
                $this->db->insert(
                    $this->db->prefix . 'stores',
                    [
                        'user_id' => $this->userId,
                        'name'    => $name,
                        'number'  => $number,
                        'street'  => $street,
                        'city'    => $city,
                        'zip'     => $zip,
                    ],
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ]
                );

                // Set an initial master list order for the new store
                // ...should have a dropdown that the user can select existing store template to use here
                $this->initializeOrderedGroceryStoreItems($this->userId, $this->db->insert_id);
            } else {
                $this->db->update(
                    $this->db->prefix . 'stores',
                    [
                        'name'   => $name,
                        'number' => $number,
                        'street' => $street,
                        'city'   => $city,
                        'zip'    => $zip,
                    ],
                    [
                        'id' => $storeId,
                    ],
                    [
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );
            }
        }
    }


    /**
     * Return a user's stores
     * @return arr    User store list
     */
    public function getStores()
    {
        return $this->db->get_results("SELECT * FROM wp_stores WHERE user_id = $this->userId ORDER BY id ASC");
    }


    /**
     * Check if user has saved store(s)
     *
     * @return bool    True if user has saved store(s); False otherwise
     */
    public function exists()
    {
        return ($this->getStores()) ? true : false;
    }


    /**
     * Return a single store object
     * @param  int   $storeId    Store identifier
     * @return obj               Store object
     */
    private function getStore($storeId = null)
    {
        if (! $storeId) {
            return false;
        }

        $store = $this->db->get_results("SELECT * FROM wp_stores WHERE id = $storeId");

        if (! empty($store)) {
            return $store[0];
        }

        return false;
    }


    /**
     * Handle user input from store wp-admin page
     */
    public function saveStores()
    {
        // Save new user store input
        $this->setStore();

        if (isset($_POST['store_ids']) && ! empty($_POST['store_ids'])) {
            $arrStoreIds = explode(',', $_POST['store_ids']);
        }

        // Save existing store modifications
        if (! empty($arrStoreIds)) {
            foreach ($arrStoreIds as $id) {
                $storeId = shsSanitize($id, 'i');
                $this->setStore($storeId);
            }
        }
    }


    /**
     * Handle store deletion
     * @param  int   $storeId    Store identifier
     *
     * @return bool    True if deleted; False otherwise
     */
    public function deleteStore($storeId = null)
    {
        if (! $storeId) {
            return false;
        }

        $status = $this->db->delete(
            $this->db->prefix . 'stores',
            [
                'id' => $storeId,
            ],
            [
                '%d',
            ]
        );

        return $status;
    }


    /**
     * Verify that a store exists
     * @param  arr   $stores     Existing user stores
     * @param  int   $storeId    Store identifier
     *
     * @return bool    True if exists; False otherwise
     */
    public function isExistingStore($stores = [], $storeId = null)
    {
        $storeIds = [];
        foreach ($stores as $store) {
            $storeIds[] = $store->id;
        }

        if (in_array($storeId, $storeIds)) {
            return true;
        }

        return false;
    }


    /**
     * Set user favorite store
     * @param  int   $storeId    Store identifier
     *
     * @return void
     */
    private function setFavoriteStore($storeId)
    {
        update_user_meta($this->userId, '_favorite_store', $storeId);
    }


    /**
     * Set user favorite store
     * @param  int   $storeId    Store identifier
     *
     * @return void
     */
    public function saveFavoriteStore($storeId = null)
    {
        if ($storeId) {
            $this->setFavoriteStore($storeId);
        }
    }


    /**
     * Return user favorite store
     * @param  arr   $stores     Existing user stores
     *
     * @return int               Favorite store identifier or false if not selected
     */
    private function getFavoriteStore($stores = [])
    {
        $userFavoriteStoreId = get_user_meta($this->userId, '_favorite_store', true);
        if ($userFavoriteStoreId && $this->isExistingStore($stores, $userFavoriteStoreId)) {
            $this->id = $userFavoriteStoreId;
            return $userFavoriteStoreId;
        } else {
            delete_user_meta($this->userId, '_favorite_store');
        }

        return false;
    }


    /**
     * Return favorite user store
     * @param  arr   $stores     Existing user stores
     *
     * @return int               Favorite store identifier or false if not selected
     */
    public function getFirstStore($stores = [])
    {
        if (! empty($stores) && isset($stores[0]->id)) {
            return $stores[0]->id;
        }

        return false;
    }


    /**
     * Return default user store
     * @param  arr   $stores     Existing user stores
     *
     * @return int               Default store identifier
     */
    private function getDefaultStore($stores = [])
    {
        if (! $defaultStoreId = $this->getFavoriteStore($stores)) {
            $defaultStoreId = $this->getFirstStore($stores);
        }

        $this->id = $defaultStoreId;
        return $this->id;
    }


    /**
     * Render stores in wp-admin
     *
     * @return string    Markup
     */
    public function showStores()
    {
        $output = '';
        $stores = $this->getStores();

        if (! empty($stores)) {
            // Get user favorite store
            $this->getDefaultStore($stores);

            // Collect store IDs and save to hidden field to aid form handling
            $arrStoreIds = [];

            foreach ($stores as $s) {
                $arrStoreIds[] = $s->id;

                $output .= '<div class="list-box existing">';
                $output .= '   <div class="store-mgmt">';

                $output .= '      <span class="delete-store fa fa-times-circle-o fa-3x" id="delete_' . $s->id . '"></span>';

                if ($s->id == $this->id) {
                    $output .= '  <span class="favorite-store fa fa-star fa-3x" id="favorite_' . $s->id . '"></span>';
                } else {
                    $output .= '  <span class="favorite-store fa fa-star-o fa-3x" id="favorite_' . $s->id . '"></span>';
                }

                $output .= '   </div>';
                $output .= '   <div class="store-input">';
                $output .= '       <div class="clearfix"></div>';
                $output .= '       <input type="text" name="name_' . $s->id . '" id="name_' . $s->id . '" class="half" placeholder="Store Name*" value="' . $s->name . '" />';
                $output .= '       <input type="text" name="number_' . $s->id . '" id="number_' . $s->id . '" class="half" placeholder="Store Number" value="' . $s->number . '" />';
                $output .= '       <input type="text" name="street_' . $s->id . '" id="street_' . $s->id . '" class="full" placeholder="Street" value="' . $s->street . '" />';
                $output .= '       <input type="text" name="city_' . $s->id . '" id="city_' . $s->id . '" class="half" placeholder="City" value="' . $s->city . '" />';
                $output .= '       <input type="text" name="zip_' . $s->id . '" id="zip_' . $s->id . '" class="half" placeholder="Zip" value="' . $s->zip . '" />';
                $output .= '   </div>';
                $output .= '</div>';
            }

            $storeIds = implode(',', $arrStoreIds);
            $output .= '<input type="hidden" name="store_ids" id="store_ids" value="' . $storeIds . '" />';

            return $output;
        }
    }


    /**
     * Allow user to select a store from the front-end grocery list dropdown
     */
    private function selectStore()
    {
        if (isset($_GET['sid']) && ! empty($_GET['sid'])) {
            // Select from store dropdown
            $this->id = shsSanitize($_GET['sid'], 'i');
        } else {
            // Get user default (favorite if selected)
            $stores = $this->getStores();
            $this->getDefaultStore($stores);
        }
    }


    /**
     * Render store dropdown to grocery list (front-end) page
     *
     * @return string    Markup
     */
    public function renderStoreDropdown()
    {
        $output = '';

        // Get all stores
        $stores = $this->getStores();
        $this->selectStore();

        $output .= '<div class="store-dropdown">';

        $output .= '  <span>Saved Stores</span>';
        $output .= '  <select name="user_stores" id="store_dropdown" size="1">';

        foreach ($stores as $s) {
            $selected = ($s->id == $this->id) ? ' selected="selected"' : '';
            $output .= '  <option value="' . $s->id . '"' . $selected . '>' . $s->name . '</option>';
        }

        $output .= '  </select>';
        $output .= '</div>';

        $output .= '<div class="clearfix"></div>';

        return $output;
    }


    /**
     * Retrieve/Render a store name
     * @param  obj  $store      Store
     * @param  bool $echo       If true, echo name
     * @return str              Store name or false if store does not exist
     */
    public function fetchStoreName($store, $echo = false)
    {
        if (! $store) {
            return false;
        }

        $name = $store->name;

        if ($echo) {
            echo $name;
        }

        return $name;
    }


    /**
     * Retrieve/Render a store name (wrapper)
     * @param  int  $storeId    Store identifier
     * @param  bool $echo       If true, echo name
     * @return str              Store name or false if store does not exist
     */
    public function getStoreName($storeId, $echo = false)
    {
        $store = $this->getStore($storeId);
        return $this->fetchStoreName($store, $echo);
    }


    /**
     * Start a user/store combo off with this arbitrary list of grocery items by aisle
     * @param  integer $userId     WP user id
     * @param  integer $storeId    Store identifier
     */
    public function initializeOrderedGroceryStoreItems($userId, $storeId)
    {
        $readableList = [
            'carrot',
            'spinach',
            'tofu',
            'avocado',
            'tomato',
            'jalapeno',
            'bell pepper',
            'banana',
            'squash',
            'asparagus',
            'eggplant',
            'sweet potato',
            'green onion',
            'red onion',
            'onion',
            'garlic',
            'lemon',
            'lime',
            'peanut butter',
            'bread',
            'cashews',
            'tortillas',
            'broth',
            'oats',
            'applesauce',
            'black beans',
            'garbonzo beans',
            'corn',
            'peas',
            'barbecue sauce',
            'mustard',
            'rice',
            'soy sauce',
            'hot sauce',
            'enchilada sauce',
            'chiles',
            'salsa',
            'tomato puree',
            'spaghetti sauce',
            'macaroni',
            'bread crumbs',
            'sun-dried-tomato',
            'olive oil',
            'flour',
            'pie crust',
            'chocolate chips',
            'sugar',
            'brown sugar',
            'coconut milk',
            'curry paste',
            'pumpkin',
            'basil',
            'cayenne',
            'chili powder',
            'cilantro',
            'cinnamon',
            'cumin',
            'dill',
            'garlic powder',
            'mustard powder',
            'parsley',
            'pepper',
            'pumpkin pie spice',
            'salt',
            'vanilla extract',
            'baking soda',
            'cheese',
            'feta',
            'parmesan',
            'ricotta',
            'milk',
            'half-n-half',
            'yogurt',
            'eggs',
            'butter',
        ];

        // Translate names to ingredient taxonomy IDs
        $ingredientTranslator = new IngredientTranslator();
        $initialList = $ingredientTranslator->toTaxIds($readableList);

        $masterList = new MasterList();
        $masterList->initializeList($initialList, $storeId);
    }
}
