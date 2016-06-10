/**
 * Created by wechsler on 15/04/2016.
 */
(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'SettingsCtrl', ['$scope', '$http', 'csrf',
        function($scope, $http, csrf) {

          $scope.settings = false; // = not loaded
          $scope.status = false;
          var savedHash = false;

          $http.get('/api/siteSettings').success(
            function(data) {
              $scope.settings = data;
              savedHash = angular.toJson(data);
            }
          ).error(
            function(data) {
              if (console) {
                console.log(['Error loading data', data]);
              }
            }
          );

          $scope.save = function() {
            $scope.status = 'Saving';

            var settingsMap = {};

            angular.forEach($scope.settings, function(info, key) {
                settingsMap[key] = info.settingValue;
              }
            );

            $http.post('/api/siteSettings', $.param({data: settingsMap, token: csrf}))
              .success(
                function(data) {
                  $scope.settings = data;
                  savedHash = angular.toJson(data);
                  console.log('OK');
                  $scope.status = 'Saved';
                }
              ).error(
              function(error) {
                console.error(error);
                $scope.status = 'Error';
              }
            );
          };


          $scope.changed = function() {
            return savedHash && (savedHash !== angular.toJson($scope.settings));
          }
        }
      ]);
})();
