/**
 * Created by wechsler on 22/12/2015.
 */

(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'WeddingSignupCtrl', ['$scope', '$http', 'csrf', 'settings', function($scope, $http, csrf, settings) {

        $scope.saved = false;
        $scope.errors = [];
        $scope.displayRequirements = {};

        $scope.guestData = {
          mainContact: {
            name: null,
            email: null,
            rsvp: null,
            requirements: null
          },
          otherGuests: [
            {
              name: null,
              email: null,
              rsvp: null,
              requirements: null
            }
          ]
        };

        $scope.settings = settings ? settings : {};

        $http.get('/api/contacts')
          .success(
            function(data) {
              if (data.mainContact && (data.mainContact.name || data.mainContact.email)) {
                $scope.guestData = data;
              } else {
                //pre-fill from user record
                $http.get('/api/user').success(function(data) {
                  $scope.guestData.mainContact.name = data.name;
                  $scope.guestData.mainContact.email = data.email;
                });
              }
              ensureOtherGuestsRow();
              //console.log(['Set guest data from api', data]);
            }
          )
          .error(
            function(data) {
              if (console) {
                console.log(['Error loading data', data]);
              }
            }
          );


        $scope.addGuest = function() {
          $scope.guestData.otherGuests.push({
            name: null,
            email: null,
            rsvp: null,
            requirements: null
          });
        };


        $scope.saveContacts = function() {
          $scope.saved = false;
          var url = '/api/contacts';
          $http.post(url, $.param({data: $scope.guestData, token: csrf}))
            .success(
              /**
               *
               * @param data API return
               * @param data.changeCount number of API write operations performed
               */
              function(data) {
                $scope.saved = true;
                $scope.errors = [];
                $scope.guestData = data;
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

        $scope.toggleRequirementsDisplay = function(which) {
          $scope.displayRequirements[which] =
            $scope.displayRequirements.hasOwnProperty(which) ? !$scope.displayRequirements[which] : true;
        };

        function ensureOtherGuestsRow() {
          if (!$scope.guestData.hasOwnProperty('otherGuests')) {
            $scope.guestData.otherGuests = [];
          }
          if (!$scope.guestData.otherGuests.length) {
            $scope.addGuest();
          }
        }
      }]
    );
})();