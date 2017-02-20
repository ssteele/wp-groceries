var php;


/**
 * Control function for grocery list admin
 */
function IngredientsCtrl($scope) {

    $scope.cart = [];
    $scope.recipes = php.rcps;
    $scope.ingredients = php.ingr;
    $scope.list_ingredients = php.list;

    $scope.addRecipeToList = function(item) {

        $scope.cart.push(item);
        $scope.search_recipe.name = null;

    };

    $scope.isOnList = function(item) {

        for (var i = 0; i < $scope.cart.length; i++) {
            if (item.name === $scope.cart[i].name) {
                return true;
            }
        }

        return false;
    };

    $scope.addIngredientToList = function(item) {

        if (! $scope.isOnList(item)) {
            $scope.cart.push(item);
            $scope.search_ingredient.name = null;
        }

    };

    $scope.addNewIngredientToList = function(item) {

        var new_item = {};

        new_item.type = 'new_ingredient';
        new_item.id = item.name;
        new_item.name = item.name;

        if (! $scope.isOnList(item)) {
            $scope.cart.push(new_item);
            $scope.search_ingredient.name = null;
        }

    };

    $scope.renderUnknownIngredient = function(item) {

        // Make sure an item was passed in
        var is_item = (typeof item !== 'undefined' && item.name !== null ) ? true : false;

        // Initialize unknown
        var is_known = false;

        if (is_item) {

            // If the item is not in our list of ingredients, 'in_array' will be -1 (instead of false for some reason)
            var in_array = jQuery.inArray(item.name, $scope.list_ingredients);

            if (-1 !== in_array) {
                is_known = true;
            }

        }

        var render = (is_item && !is_known);
        return render;

    };

}