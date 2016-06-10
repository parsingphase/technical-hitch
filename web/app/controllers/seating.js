/**
 * Created by wechsler on 19/04/2016.
 */
(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'SeatingCtrl', ['$scope', '$http', 'csrf',
        function($scope, $http, csrf) {

          $scope.blocks = [];
          $scope.seatLayout = [];
          $scope.error = false;
          $scope.loaded = false;
          $scope.mode = 'editPlaces'; // or editTables

          var blocksHashSaved = false;

          loadSeating();

          $scope.guestCount = function(parties) {
            var guestCount = 0;
            if (parties) {
              for (var i = 0; i < parties.length; i++) {
                guestCount += parties[i].guests ? parties[i].guests.length : 0;
              }
            }

            return guestCount;
          };

          $scope.savePlaces = function() {
            var blocksByParty = {};
            for (var i = 0; i < $scope.blocks.length; i++) {
              var block = $scope.blocks[i];
              // console.log('block ' + i);
              // console.log(block);
              if (block) {
                for (var j = 0; j < block.length; j++) {
                  var party = block[j];
                  // console.log('party ' + party.userId);
                  blocksByParty[party.userId] = i;
                }
              }
            }

            $http.post('/api/seating', $.param({partySeating: blocksByParty, token: csrf}))
              .success(
                function(data) {
                  $scope.status = 'Saved';
                  blocksHashSaved = angular.toJson($scope.blocks);
                }
              ).error(
              function(error) {
                $scope.status = 'Error';
              }
            );
          };

          $scope.blocksChanged = function(blocks) {
            return (angular.toJson(blocks) !== blocksHashSaved);
          };

          $scope.saveSeating = function() {
            console.log('saveSeating');
            console.log($scope.seatLayout);
            var tablesFlat = [];
            for (var i = 0; i < $scope.seatLayout.length; i++) {
              var seatSet = $scope.seatLayout[i];
              for (var j = 0; j < seatSet.length; j++) {
                tablesFlat.push(seatSet[j]);
              }
            }

            console.log(tablesFlat);

            $http.post('/api/tableLayout', $.param({layout: tablesFlat, token: csrf}))
              .success(
                function(data) {
                  $scope.status = 'Saved (reload for position changes)';
                  $scope.seatLayout = data;
                }
              ).error(
              function(error) {
                $scope.status = 'Error';
              }
            );
          };

          function loadParties() {
            $http.get('/api/attendingParties')
              .success(
                function(data) { // one datum = one party
                  resetBlocks(10);
                  for (var i = 0; i < data.length; i++) {
                    var party = data[i];
                    // console.log(party);
                    var blockId = party.blockId + 0;
                    if (!$scope.blocks[blockId]) {
                      $scope.blocks[blockId] = [];
                    }
                    $scope.blocks[blockId].push(party);
                  }
                  $scope.loaded = true;
                  // console.log($scope.blocks);
                  blocksHashSaved = angular.toJson($scope.blocks);
                }
              )
              .error(
                function(data) {
                  if (console) {
                    console.log(['Error loading data', data]);
                  }
                  $scope.error = 'Error loading data';
                }
              );
          }

          function loadSeating() {
            $http.get('/api/seating')
              .success(
                function(data) {
                  $scope.seatLayout = data;
                  //FIXME get max from blocks for i
                  loadParties();
                }
              )
              .error(
                function(data) {
                  if (console) {
                    console.log(['Error loading data', data]);
                    $scope.error = 'Error loading data';
                  }
                }
              );
          }

          function resetBlocks(maxBlock) {
            $scope.blocks = [];
            for (var i = 0; i < maxBlock; i++) {
              $scope.blocks[i] = [];
            }
          }
        }
      ]);
})();
