/**
 * Created by wechsler on 16/04/2016.
 */

(function () { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp')
    .directive('adminSettings', function () {
      return {
        restrict: "E",
        templateUrl: '/app/templates/adminSettings.html',
        scope: {},
        controller: 'SettingsCtrl'
      };
    });
})();