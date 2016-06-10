/**
 * Created by wechsler on 01/01/2016.
 */

(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'WeddingUserAdminCtrl', [
        '$scope', '$http', 'csrf', 'ngDialog', '$rootScope', function($scope, $http, csrf, ngDialog, $rootScope) {

          $scope.view = 'summary';
          $scope.guestsWithRequirements = [];
          $scope.menuChoices = [];
          $scope.ageGroupCount = false;

          var rolesDescending = [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_WEDDING_GUEST',
            'ROLE_PREVIEW_GUEST',
            'ROLE_OBSERVER',
            'ROLE_USER'
          ];

          $scope.users = [];

          $http.get('/api/users').success(
            function(data) {
              for (var i = 0; i < data.length; i++) {
                var user = data[i];

                user.maxRole = null;
                user.roleChanged = false;
                user.roleUpdateFailed = false;

                user.maxRole = findMaxRole(user.user.roles);

              }
              $scope.users = data;
              $scope.rsvpTotals = buildGuestSummaries();
            }
          ).error(
            function(data) {
              if (console) {
                console.log(['Error loading data', data]);
              }
            }
          );

          $scope.updateUserRole = function(userId, role) {
            //console.log('updateUserRole(' + userId + ',' + role + ')');
            findUserById(userId, function(user) {
              user.roleChanged = true;
            });

            var params = {
              userId: userId,
              role: role,
              token: csrf
            };

            $http.post('/api/updateUserRole', $.param(params)).success(
              function(data) {
                findUserById(userId, function(user) {
                  user.roleChanged = false;
                  user.roleUpdateFailed = false;
                  user.maxRole = findMaxRole(data.user.roles);
                });
              }
            ).error(
              function() {
                findUserById(userId, function(user) {
                  user.roleChanged = false;
                  user.roleUpdateFailed = true; // only set back on successful save, not on attempt
                });
              }
            );
          };

          $scope.sortUsersBy = function(sortType, reverse) {
            //console.log('sortUsersBy: ' + sortType + (reverse ? ' - ' : ''));
            var compare = function(a, b) {
              if (a > b) {
                return 1;
              } else if (a < b) {
                return -1;
              }
            };

            var rsvpSummary = function(contactData) {
              //ATTENDING, NOT ATTENDING, TBC, ZERO REPLIES
              var states = [null, null, null];
              var summary;
              if (contactData && contactData.length) {
                for (var i = 0; i < contactData.length; i++) {
                  var datum = contactData[i].rsvp;
                  switch (datum) {
                    case 'ATTENDING':
                      states[0] = datum;
                      break;
                    case 'NOT ATTENDING':
                      states[1] = datum;
                      break;
                    default:
                      states[2] = 'TBC';
                  }
                }
                summary = states.join('');
              } else {
                summary = 'ZERO REPLIES';
              }
              //console.log(['rsvpSummary', contactData, summary]);
              return (summary);
            };

            var sortFunctions = {
              username: function(a, b) {
                return compare(a.user.name, b.user.name);
              },
              email: function(a, b) {
                return compare(a.user.email, b.user.email);
              },
              enabled: function(a, b) {
                var primary = compare(a.user.enabled, b.user.enabled);
                return primary ? primary : compare(a.user.name, b.user.name);
              },
              role: function(a, b) {
                var primary = compare(a.maxRole, b.maxRole);
                return primary ? primary : compare(a.user.name, b.user.name);
              },
              rsvps: function(a, b) {
                var primary = compare(rsvpSummary(a.contactData), rsvpSummary(b.contactData));
                return primary ? primary : compare(a.user.name, b.user.name);
              }
            };

            var reverseFunc = function(sortFunc) {
              return function(a, b) {
                return 0 - sortFunc(a, b);
              };
            };

            if (sortFunctions.hasOwnProperty(sortType)) {
              $scope.users = $scope.users.sort(reverse ? reverseFunc(sortFunctions[sortType]) : sortFunctions[sortType]);
            }
          };

          $scope.showRequirements = function(contact) {
            var innerScope = $rootScope.$new();
            innerScope.contact = contact;
            ngDialog.open({
              template: '/app/templates/contactRequirements.html',
              scope: innerScope,
              className: 'ngdialog-theme-default contactRequirementsPopup'
            });
          };

          $scope.limitString = function(string, maxlength) {
            if (string.length > maxlength) {
              string = string.substring(0, maxlength) + '…';
            }
            return string;
          };

          $http.get('/api/menuChoices').success(
            function(data) {
              $scope.menuChoices = data;
            }
          ).error(
            function(data) {
              if (console) {
                console.log(['Error loading menu data', data]);
              }
            }
          );


          //----- Internal functions -----

          function findUserById(userId, callback) {
            var user = null;
            for (var i = 0; i < $scope.users.length; i++) {
              if ($scope.users[i].user.id == userId) {
                callback($scope.users[i]);
              }
            }
            return user;
          }

          function findMaxRole(roles) {
            var maxRole = false;
            roleLoop:
              for (var rIdx = 0; rIdx < rolesDescending.length; rIdx++) {
                var checkRole = rolesDescending[rIdx];
                for (var urIdx = 0; urIdx < roles.length; urIdx++) {
                  var userRole = roles[urIdx];
                  if (checkRole === userRole) {
                    maxRole = checkRole;
                    break roleLoop;
                  }
                }
              }
            //console.log('findMaxRole(' + roles + ') = ' + maxRole)
            return maxRole;
          }

          function buildGuestSummaries() {
            $scope.guestsWithRequirements = [];
            $scope.ageGroupCount = {};
            var totals = {yes: 0, no: 0};
            for (var i = 0; i < $scope.users.length; i++) {
              var contactData = $scope.users[i].contactData;
              for (var j = 0; j < contactData.length; j++) {
                console.log(contactData[j]);
                var datum = contactData[j].rsvp;
                var age = (contactData[j].menu && contactData[j].menu.type) ? contactData[j].menu.type : '?';
                switch (datum) {
                  case 'ATTENDING':
                    totals.yes++;
                    if (!$scope.ageGroupCount[age]) {
                      $scope.ageGroupCount[age] = {
                        count: 0,
                        choices: {starter: {}, main: {}, dessert: {}}
                      };
                    }
                    $scope.ageGroupCount[age].count++;

                    if ($scope.isAdultMealAge(age)) {
                      // --
                      if (!$scope.ageGroupCount[age].choices.starter.hasOwnProperty(contactData[j].menu.starter)) {
                        $scope.ageGroupCount[age].choices.starter[contactData[j].menu.starter] = 0;
                      }
                      $scope.ageGroupCount[age].choices.starter[contactData[j].menu.starter]++;
                      // --
                      if (!$scope.ageGroupCount[age].choices.main.hasOwnProperty(contactData[j].menu.main)) {
                        $scope.ageGroupCount[age].choices.main[contactData[j].menu.main] = 0;
                      }
                      $scope.ageGroupCount[age].choices.main[contactData[j].menu.main]++;
                      // --
                      if (!$scope.ageGroupCount[age].choices.dessert.hasOwnProperty(contactData[j].menu.dessert)) {
                        $scope.ageGroupCount[age].choices.dessert[contactData[j].menu.dessert] = 0;
                      }
                      $scope.ageGroupCount[age].choices.dessert[contactData[j].menu.dessert]++;
                      // -
                    }

                    break;
                  case 'NOT ATTENDING':
                    totals.no++;
                    break;
                }

                if (contactData[j].requirements) {
                  $scope.guestsWithRequirements.push(contactData[j]);
                }
              }
            }
            return totals;
          }

          $scope.isAdultMealAge = function(age) {
            return ((age === 'Adult') || (age === 'Teen'));
          };
        }]
    );
})();
