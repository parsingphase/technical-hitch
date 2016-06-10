/**
 * Created by wechsler on 28/04/2016.
 */
(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'profileCtrl', ['$scope', '$http', 'csrf', 'profileId',
        function($scope, $http, csrf, profileId) {
          $scope.model = false;
          $scope.mode = 'view';
          $scope.saved = false;
          $scope.valid = {
            facebook: true,
            twitter: true,
            photoUrl: true,
            otherLinkUrl: true,
            otherLinkName: true
          };
          $scope.allValid = true;
          $scope.errors = [];

          var hashAsSaved = null;

          $scope.view = function() {
            window.location.reload();
          };

          $scope.edit = function() {
            $scope.mode = 'edit';
            loadProfile();
          };

          $scope.save = function() {
            $scope.saved = false;
            var url = '/api/profile';

            $http.post(url, $.param({profile: $scope.model, token: csrf}))
              .success(
                /**
                 *
                 * @param data API return
                 * @param data.changeCount number of API write operations performed
                 */
                function(data) {
                  $scope.saved = true;
                  $scope.errors = [];
                  $scope.model = data;
                  hashAsSaved = angular.toJson($scope.model);
                  // window.location.reload();
                }
              )
              .error(
                /**
                 *
                 * @param data data API return
                 * @param data.errors List of error messages
                 */
                function(data) {
                  if (data.hasOwnProperty('errors')) {
                    $scope.errors = data.errors;
                  } else {
                    $scope.errors = ['An unknown error occurred.'];
                  }
                }
              );
          };

          function loadProfile() {
            $http.get('/api/profile/' + profileId)
              .success(
                function(data) {
                  $scope.model = data;
                }
              )
              .error(
                function(data) {
                  if (console) {
                    console.log(['Error loading data', data]);
                  }
                }
              );
          }

          $scope.$watch(
            function() {
              return angular.toJson($scope.model);
            },
            function() {
              if (!$scope.model) {
                return;
              }

              var hash = angular.toJson($scope.model);
              if (hash !== hashAsSaved) {
                $scope.saved = false;
              }

              //Model fields are SET UNDEFINED (but still present in model) when form input is invalid
              // Undefined fields are NOT shown in template (eg {[{ model }]}) (but are still present in code)
              //'allowMessages', 'facebook', 'twitter', 'photoUrl', 'otherLinkUrl', 'otherLinkName'
              $scope.valid.twitter =
                ($scope.model.hasOwnProperty('twitter')) && // Present in model
                (!angular.isUndefined($scope.model.twitter)) && // hasn't been undefined by form validation?
                (Boolean($scope.model.twitter.match(/^@\w+$/)) || !$scope.model.twitter.length);

              // console.log($scope.model);

              // Could still be invalid by
              $scope.valid.facebook =
                ($scope.model.hasOwnProperty('facebook')) && // Present in model
                (!angular.isUndefined($scope.model.facebook)) && // hasn't been undefined by form validation?
                (Boolean($scope.model.facebook.match(/^http(s?):\/\/www.facebook.com\/\w+/)) || !$scope.model.facebook.length);

              $scope.valid.photoUrl =
                ($scope.model.hasOwnProperty('photoUrl')) && // Present in model
                (!angular.isUndefined($scope.model.photoUrl));

              $scope.valid.otherLinkUrl =
                ($scope.model.hasOwnProperty('otherLinkUrl')) && // Present in model
                (!angular.isUndefined($scope.model.otherLinkUrl));

              $scope.valid.otherLinkName =
                Boolean(Boolean($scope.model.otherLinkUrl && $scope.model.otherLinkUrl.length) ? ($scope.model.otherLinkName.length ) : true);
              // Label required if URL

              $scope.allValid = $scope.valid.twitter &&
                $scope.valid.facebook &&
                $scope.valid.photoUrl &&
                $scope.valid.otherLinkUrl &&
                $scope.valid.otherLinkName;
            }
          );


        }]);
})();