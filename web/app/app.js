/**
 * Created by wechsler on 20/12/2015.
 */

(function() { //IIFE to enable strict mode
  'use strict';

  angular.module('weddingApp', ['ngDialog', 'ngDragDrop'])
    .config(['$interpolateProvider', function($interpolateProvider) {
      // Use a boundary symbol that Twig won't consume:
      // See https://docs.angularjs.org/api/ng/provider/$interpolateProvider
      $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
    }])
    // Angular & PHP do not play nice over POST.
    // See http://stackoverflow.com/questions/15485354/angular-http-post-to-php-and-undefined
    // and use $.param in $http()
    .config(['$httpProvider', function($httpProvider) {
      $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
    }])
    //Create ucFirst filter
    .filter('ucFirst', function() {
      return function(input) {
        if (input) {
          input = input.toLowerCase();
          input = input.substring(0, 1).toUpperCase() + input.substring(1);
        }
        return input;
      }
    }).filter('truncate', function() {
      return function(input, length) {
        return input.substring(0, length);
      };
    }
  );
})();