/**
 * Created by wechsler on 05/01/2016.
 */
(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'MenuSelectionCtrl', ['$scope', '$http', 'csrf', 'settings',
        function($scope, $http, csrf, settings) {

          $scope.loaded = false;
          $scope.savedHash = null;
          $scope.storeMessage = null; // result, text

          /**
           * Also used as trigger to remove 'saved' message
           * @returns {string|undefined|*}
           */
          $scope.generateScopeHash = function() {
            var hash = angular.toJson(
              [
                $scope.menus,
                $scope.Starter,
                $scope.Main,
                $scope.Dessert,
                $scope.preferences
              ]
            );
            if ((hash !== $scope.savedHash) && $scope.storeMessage && ($scope.storeMessage.result == 'success')) {
              $scope.storeMessage = null;
            }
            return hash;
          };

          $scope.settings = settings ? settings : {};

          function resetForm() {
            $scope.menus = {};
            $scope.Starter = {};
            $scope.Main = {};
            $scope.Dessert = {};
            $scope.preferences = {};
          }

          resetForm();

          $scope.isAdultMealAge = function(age) {
            return ((age === 'Adult') || (age === 'Teen'));
          };

          $scope.isChildMealAge = function(age) {
            return ((age === 'Child') || (age === 'Under4'));
          };

          $scope.menuRowClass = function(choice, course, userKey) {
            var selection = $scope[course].hasOwnProperty(userKey) ? $scope[course][userKey] : 'tbc';
            if ((selection == 'tbc') || !selection) {
              return 'tbc';
            } else {
              return (selection == choice) ? 'selected' : 'unselected';
            }
          };

          $scope.setDefaults = function(guestId) {
            var models = ['Starter', 'Main', 'Dessert'];
            for (var i = 0; i < models.length; i++) {
              var model = models[i];
              if (!$scope[model].hasOwnProperty(guestId)) {
                $scope[model][guestId] = 'tbc';
              }
            }
          };

          $scope.saveChoices = function() {
            var saveData = {};
            for (var key in $scope.menus) {
              if ($scope.menus.hasOwnProperty(key) && key.match(/^\d+$/)) {
                // safe guest ident
                saveData[key] = {
                  menu: $scope.menus[key],
                  starter: $scope.Starter[key],
                  main: $scope.Main[key],
                  dessert: $scope.Dessert[key],
                  preferences: $scope.preferences[key]
                }
              }
            }
            var message = {token: csrf, choices: saveData};
            console.log(message);
            $http.post('/api/menuchoice', $.param(message)).success(
              function(data) {
                importChoices(data);
                $scope.storeMessage = {text: 'Saved choices', result: 'success'}; // after import or it gets hidden
              }
            ).error(
              function(data) {
                //console.error('Bad menu store');
                if (data.hasOwnProperty('errors')) {
                  $scope.storeMessage = {text: errors.join(', '), result: 'danger'};
                } else {
                  $scope.storeMessage = {text: 'An unknown error occurred.', result: 'danger'};
                }
              }
            );
          };

          $http.get('/api/menuchoice')
            .success(importChoices)
            .error(function(data) {
                if (data.hasOwnProperty('errors')) {
                  $scope.storeMessage = {text: errors.join(', '), result: 'danger'};
                } else {
                  $scope.storeMessage = {text: 'An unknown error occurred.', result: 'danger'};
                }
              }
            );


          function importChoices(data) {
            resetForm();
            for (var i = 0; i < data.length; i++) {
              var response = data[i];
              var guestId = response.guestId;
              $scope.menus[guestId] = response.menu;
              $scope.Starter[guestId] = response.starter ? response.starter : 'tbc';
              $scope.Main[guestId] = response.main ? response.main : 'tbc';
              $scope.Dessert[guestId] = response.dessert ? response.dessert : 'tbc';
              $scope.preferences[guestId] = response.preferences;
            }
            $scope.savedHash = $scope.generateScopeHash();
            $scope.loaded = true;
          }

        }
      ]);
})();