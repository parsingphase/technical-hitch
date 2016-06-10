/**
 * Created by wechsler on 13/01/2016.
 */


(function () { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp')
    .directive('weddingMailer', function () {
      return {
        restrict: "E",
        templateUrl: '/app/templates/weddingMailer.html',
        scope: {},
        controller: 'WeddingMailerCtrl'
      };
    });
})();