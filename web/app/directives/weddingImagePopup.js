/**
 * Created by wechsler on 22/12/2015.
 */


(function() { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp')
    .directive('weddingImagePopup', function() {
      return {
        restrict: "E",
        templateUrl: '/app/templates/weddingImagePopup.html',
        scope: {
          image: '=',
          width: '=',
          widthlarge: '=',
          title: '='
        },
        controller: 'WeddingImagePopupCtrl'
      };
    });
})();