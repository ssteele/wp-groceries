<?php

class groceryStores {


    // Declare properties
    public $id;
    public $user_id;


    /**
     * Construct method
     * @param integer $user_id    WP user id
     */
    function __construct($user_id=null) {

        if (!isset($user_id)) {
            $user_id = get_current_user_id();
        }

        $this->user_id = $user_id;

    }


    /**
     * Save new and existing user stores
     * @param int $store_id    Store identifier
     */
    private function set_store($store_id=null) {

        // Flage new or existing store
        $is_new = is_null($store_id) ? true : false;

        // Handle user input
        $post_vars = array('name', 'number', 'street', 'city', 'state', 'zip');

        foreach ($post_vars as $p) {

            $var = ($is_new) ? $p : $p . '_' . $store_id;

            if (isset($_POST[$var]) && !empty($_POST[$var])) {
                $$p = sanitize_input($_POST[$var], 's');
            } else {
                $$p = '';
            }

        }

        // Name field required to save store
        if (!empty($name)) {

            global $wpdb;

            if ($is_new) {

                $wpdb->insert(
                    $wpdb->prefix . 'stores',
                    array(
                        'user_id' => $this->user_id,
                        'name'    => $name,
                        'number'  => $number,
                        'street'  => $street,
                        'city'    => $city,
                        'state'   => $state,
                        'zip'     => $zip,
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    )
                );

                // Set an initial master list order for the new store
                // ...should have a dropdown that the user can select existing store template to use here
                initialize_ordered_grocery_store_items($this->user_id, $wpdb->insert_id);

            } else {

                $wpdb->update(
                    $wpdb->prefix . 'stores',
                    array(
                        'name'   => $name,
                        'number' => $number,
                        'street' => $street,
                        'city'   => $city,
                        'state'  => $state,
                        'zip'    => $zip,
                    ),
                    array(
                        'id' => $store_id,
                    ),
                    array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ),
                    array(
                        '%d',
                    )
                );

            }

        }

    }


    private function get_stores() {

        global $wpdb;
        return $wpdb->get_results("SELECT * FROM wp_stores WHERE user_id = $this->user_id");

    }


    /**
     * Handle user input from store wp-admin page
     */
    public function save_stores() {

        // Save new user store input
        $this->set_store();

        if (isset($_POST['store_ids']) && !empty($_POST['store_ids'])) {
            $arr_store_ids = explode(',', $_POST['store_ids']);
        }

        // Save existing store modifications
        if (!empty($arr_store_ids)) {

            foreach ($arr_store_ids as $id) {

                $store_id = sanitize_input($id, 'i');
                $this->set_store($store_id);

            }

        }

    }


    /**
     * Return user favorite
     * @return int    Default store idendifier
     */
    private function get_default_store() {

         $this->id = get_user_meta($this->user_id, '_favorite_store', true);
         return $this->id;

    }


    /**
     * Render stores in wp-admin
     */
    public function show_stores() {

        $stores = $this->get_stores();

        if (isset($stores) && !empty($stores)) {

            // Get user favorite store
            $this->get_default_store();

            // Collect store IDs and save to hidden field to aid form handling
            $arr_store_ids = array();

            foreach ($stores as $s) {

                $arr_store_ids[] = $s->id;

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

            $store_ids = implode(',', $arr_store_ids);
            echo '<input type="hidden" name="store_ids" id="store_ids" value="' . $store_ids . '" />';

        }

    }


    private function select_store() {

        if (isset($_GET['sid']) && !empty($_GET['sid'])) {

            // Select from store dropdown
            $this->id = sanitize_input($_GET['sid'], 'i');

        } else {

            // Get user default (favorite)
            $this->get_default_store();

        }

    }


    /**
     * Render store dropdown to grocery list (front-end) page
     */
    public function render_store_dropdown() {

        // Get all stores
        $stores = $this->get_stores();
        $this->select_store();

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

}