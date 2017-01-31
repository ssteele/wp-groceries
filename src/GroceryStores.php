<?php

namespace SteveSteele\Groceries;

class GroceryStores
{

    // Declare properties
    public $id;
    public $userId;
    public $idForGuestUse = 1;
    public $isGuest = false;


    /**
     * Construct method
     * @param integer $userId    WP user id
     */
    public function __construct($userId = null)
    {
        $this->setUser($userId);
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
     * Save new and existing user stores
     * @param int $storeId    Store identifier
     */
    private function setStore($storeId = null)
    {

        // Flag new or existing store
        $isNew = is_null($storeId) ? true : false;

        // Handle user input
        $postVars = ['name', 'number', 'street', 'city', 'state', 'zip'];

        foreach ($postVars as $p) {
            $var = ($isNew) ? $p : $p . '_' . $storeId;

            if (isset($_POST[$var]) && ! empty($_POST[$var])) {
                $$p = sanitize_input($_POST[$var], 's');
            } else {
                $$p = '';
            }
        }

        // Name field required to save store
        if (! empty($name)) {
            global $wpdb;

            if ($isNew) {
                $wpdb->insert(
                    $wpdb->prefix . 'stores',
                    [
                        'user_id' => $this->userId,
                        'name'    => $name,
                        'number'  => $number,
                        'street'  => $street,
                        'city'    => $city,
                        'state'   => $state,
                        'zip'     => $zip,
                    ],
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ]
                );

                // Set an initial master list order for the new store
                // ...should have a dropdown that the user can select existing store template to use here
                $this->initializeOrderedGroceryStoreItems($this->userId, $wpdb->insert_id);
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'stores',
                    [
                        'name'   => $name,
                        'number' => $number,
                        'street' => $street,
                        'city'   => $city,
                        'state'  => $state,
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
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM wp_stores WHERE user_id = $this->userId");
    }


    /**
     * Return a single store object
     * @param  int   $storeId    Store identifier
     * @return obj               Store object
     */
    private function getStore($storeId)
    {
        if (! $storeId) {
            return false;
        }

        global $wpdb;
        $store = $wpdb->get_results("SELECT * FROM wp_stores WHERE id = $storeId");

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
                $storeId = sanitize_input($id, 'i');
                $this->setStore($storeId);
            }
        }
    }


    /**
     * Verify that a store exists
     * @return bool    True if exists; False otherwise
     */
    private function isExistingStore($storeId = null)
    {
        $stores = $this->getStores();
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
     * Return favorite user store
     * @return int    Favorite store identifier or false if not selected
     */
    private function getFavoriteStore()
    {
        $userFavoriteStoreId = get_user_meta($this->userId, '_favorite_store', true);
        if ($userFavoriteStoreId && $this->isExistingStore($userFavoriteStoreId)) {
            $this->id = $userFavoriteStoreId;
            return $userFavoriteStoreId;
        } else {
            delete_user_meta($this->userId, '_favorite_store');
        }

        return false;
    }


    /**
     * Return favorite user store
     * @return int    Favorite store identifier or false if not selected
     */
    private function getFirstStore()
    {
        $stores = $this->getStores();
        if (! empty($stores) && isset($stores[0]->id)) {
            return $stores[0]->id;
        }

        return false;
    }


    /**
     * Return default user store
     * @return int    Default store identifier
     */
    private function getDefaultStore()
    {
        if (! $defaultStoreId = $this->getFavoriteStore()) {
            $defaultStoreId = $this->getFirstStore();
        }

        $this->id = $defaultStoreId;
        return $this->id;
    }


    /**
     * Render stores in wp-admin
     */
    public function showStores()
    {
        $stores = $this->getStores();

        if (isset($stores) && ! empty($stores)) {
            // Get user favorite store
            $this->getDefaultStore();

            // Collect store IDs and save to hidden field to aid form handling
            $arrStoreIds = [];

            foreach ($stores as $s) {
                $arrStoreIds[] = $s->id;

                echo '<div class="list-box existing">';
                echo '   <div class="store-mgmt">';

                echo '      <span class="delete-store fa fa-times-circle-o fa-3x" id="delete_' . $s->id . '"></span>';

                if ($s->id == $this->id) {
                    echo '  <span class="favorite-store fa fa-star fa-3x" id="favorite_' . $s->id . '"></span>';
                } else {
                    echo '  <span class="favorite-store fa fa-star-o fa-3x" id="favorite_' . $s->id . '"></span>';
                }

                echo '   </div>';
                echo '   <div class="store-input">';

                echo '       <select class="state-dropdown" name="state_' . $s->id . '" size="1">';
                echo '           <option value=""></option>';
                echo '           <option value="TX" selected="selected">TX</option>';
                echo '       </select>';
                echo '       <div class="clearfix"></div>';
                echo '       <input type="text" name="name_' . $s->id . '" id="name_' . $s->id . '" class="half" placeholder="Store Name*" value="' . $s->name . '" />';
                echo '       <input type="text" name="number_' . $s->id . '" id="number_' . $s->id . '" class="half" placeholder="Store Number" value="' . $s->number . '" />';
                echo '       <input type="text" name="street_' . $s->id . '" id="street_' . $s->id . '" class="full" placeholder="Street" value="' . $s->street . '" />';
                echo '       <input type="text" name="city_' . $s->id . '" id="city_' . $s->id . '" class="half" placeholder="City" value="' . $s->city . '" />';
                echo '       <input type="text" name="zip_' . $s->id . '" id="zip_' . $s->id . '" class="half" placeholder="Zip" value="' . $s->zip . '" />';

                echo '   </div>';
                echo '</div>';
            }

            $storeIds = implode(',', $arrStoreIds);
            echo '<input type="hidden" name="store_ids" id="store_ids" value="' . $storeIds . '" />';
        }
    }


    /**
     * Allow user to select a store from the front-end grocery list dropdown
     */
    private function selectStore()
    {
        if (isset($_GET['sid']) && ! empty($_GET['sid'])) {
            // Select from store dropdown
            $this->id = sanitize_input($_GET['sid'], 'i');
        } else {
            // Get user default (favorite if selected)
            $this->getDefaultStore();
        }
    }


    /**
     * Render store dropdown to grocery list (front-end) page
     */
    public function renderStoreDropdown()
    {
        // Get all stores
        $stores = $this->getStores();
        $this->selectStore();

        echo '<div class="store-dropdown">';

        echo '  <span>Saved Stores</span>';
        echo '  <select name="user_stores" id="store_dropdown" size="1">';

        foreach ($stores as $s) {
            $selected = ($s->id == $this->id) ? ' selected="selected"' : '';
            echo '  <option value="' . $s->id . '"' . $selected . '>' . $s->name . '</option>';
        }

        echo '  </select>';
        echo '</div>';

        echo '<div class="clearfix"></div>';
    }


    /**
     * Retrieve/Render a store name
     * @param  int  $storeId    Store identifier
     * @param  bool $echo       If true, echo name
     * @return str              Store name
     */
    public function getStoreName($storeId, $echo = false)
    {
        $store = $this->getStore($storeId);

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
        $initialList = ingredients_to_tax_ids($readableList);

        $masterList = new MasterList();
        $masterList->initializeList($initialList, $storeId);
    }
}
