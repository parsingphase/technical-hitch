/**
 * Created by wechsler on 22/12/2015.
 */


(function () { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp')
    .directive('weddingSignup', function () {
      return {
        restrict: "E",
        templateUrl: '/app/templates/weddingSignup.html',
        scope: {},
        controller: 'WeddingSignupCtrl'
      };
    });
})();