/**
 * Created by wechsler on 13/01/2016.
 */

(function() { //IIFE to enable strict mode
  'use strict';
  angular.module('weddingApp')
    .controller(
      'WeddingMailerCtrl', ['$scope', '$http', 'csrf', function($scope, $http, csrf) {

        $scope.contacts = [];
        $http.get('/api/emailAddresses').success(function(data) {
          $scope.contacts = data;
        });

        $scope.messageTemplates = [];
        $http.get('/api/mailTemplates').success(function(data) {
          $scope.messageTemplates = data;
        });

        $scope.currentTemplate = null;

        $scope.createNewTemplate = function() {
          $scope.messageTemplates.push(
            {
              id: 'new',
              identifier: '',
              subject: '',
              body: ''
            }
          );
          $scope.currentTemplate = $scope.messageTemplates.length - 1;
        };

        $scope.saveCurrentTemplate = function() {
          var url = '/api/mailTemplate';
          var savedTemplateOffset = $scope.currentTemplate;
          var messageTemplate = $scope.messageTemplates[savedTemplateOffset];
          var cleanTemplate = {
            id: messageTemplate.id,
            identifier: messageTemplate.identifier,
            subject: messageTemplate.subject,
            body: messageTemplate.body
          };
          $http.post(url, $.param({data: cleanTemplate, token: csrf}))
            .success(
              function(data) {
                $scope.messageTemplates[savedTemplateOffset] = data;
                console.log('OK');
              }
            ).error(
            function(error) {
              console.error(error);
            }
          );
        };

        $scope.setCurrentTemplate = function(offset) {
          //console.log('Load template: ' + offset)
          $scope.currentTemplate = offset;
          getTemplateSentStatus();
        };

        $scope.sendCurrentTemplate = function() {
          var url = '/api/mailToRecipients';
          var savedTemplateOffset = $scope.currentTemplate;
          var messageTemplate = $scope.messageTemplates[savedTemplateOffset];
          var cleanTemplate = {
            id: messageTemplate.id,
            identifier: messageTemplate.identifier,
            subject: messageTemplate.subject,
            body: messageTemplate.body
          };
          //console.log(['Send template', messageTemplate]);
          var params = {
            template: cleanTemplate,
            recipients: selectedRecipients(),
            token: csrf
          };
          $http.post(url, $.param(params))
            .success(
              function(data) {

                for (var email in data) {
                  if (data.hasOwnProperty(email)) {
                    var sent = data[email];
                    for (var j = 0; j < $scope.contacts.length; j++) {
                      //console.log([email,$scope.contacts[j].contactEmail]);
                      if (email === $scope.contacts[j].contactEmail) {
                        if (sent) {
                          $scope.contacts[j].sendTo = false;
                        }
                      }
                    }
                  }
                }
                getTemplateSentStatus();
              }
            ).error(
            function(error) {
              console.error(error);
            }
          );
        };

        $scope.sendable = function() {
          return (($scope.currentTemplate + '').match(/^\d+$/) && selectedRecipients().length);
        };

        function selectedRecipients() {
          var selectedRecipients = [];
          for (var i = 0; i < $scope.contacts.length; i++) {
            var contact = $scope.contacts[i];
            if (contact.sendTo) {
              selectedRecipients.push(contact);
            }
          }
          return selectedRecipients;
        }

        function getTemplateSentStatus() {
          var url = '/api/mailSentStatus';
          var savedTemplateOffset = $scope.currentTemplate;
          var messageTemplate = $scope.messageTemplates[savedTemplateOffset];
          var params = {
            template: messageTemplate,
            recipients: $scope.contacts,
            token: csrf
          };
          $http.post(url, $.param(params))
            .success(
              function(data) {
                for (var i = 0; i < $scope.contacts; i++) {
                  $scope.contacts[i].sent = false;
                }

                for (var email in data) {
                  if (data.hasOwnProperty(email)) {
                    var sent = data[email];
                    for (var j = 0; j < $scope.contacts.length; j++) {
                      //console.log([email,$scope.contacts[j].contactEmail]);
                      if (email === $scope.contacts[j].contactEmail) {
                        $scope.contacts[j].sent = sent;
                      }
                    }
                  }
                }
              }
            ).error(
            function(error) {
              console.error(error);
            }
          );
        }
      }]
    )
})();