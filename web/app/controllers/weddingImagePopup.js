/**
 * Created by wechsler on 05/01/2016.
 */
(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'WeddingImagePopupCtrl', ['$scope', 'ngDialog',
        function($scope, ngDialog) {

          $scope.imageLightbox = function(image) {
            ngDialog.open({
              template: '/app/templates/imagePopupTemplate.html',
              scope: $scope,
              className: 'ngdialog-theme-default imagePopupBordered'
            });
          };
        }
      ]);
})();