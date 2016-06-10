/**
 * Created by wechsler on 01/01/2016.
 */


(function () { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp')
    .directive('weddingUserAdmin', function () {
      return {
        restrict: "E",
        templateUrl: '/app/templates/weddingUserAdmin.html',
        scope: {},
        controller: 'WeddingUserAdminCtrl'
      };
    });
})();