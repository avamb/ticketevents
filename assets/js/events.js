/**
 * Bil24 Events JavaScript
 * Handles event listing interactions, session booking, and AJAX functionality
 */

(function($) {
    'use strict';

    var Bil24Events = {
        
        /**
         * Initialize events functionality
         */
        init: function() {
            this.bindEvents();
            this.initFilters();
            this.loadSessionsForCurrentEvent();
            this.initBookingInterface();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // View toggle
            $(document).on('click', '.bil24-view-btn', this.handleViewToggle);
            
            // Filter form
            $(document).on('submit', '.bil24-filters-form', this.handleFilterSubmit);
            
            // Quick book buttons
            $(document).on('click', '.bil24-quick-book', this.handleQuickBook);
            
            // Session selection
            $(document).on('click', '.bil24-select-session', this.handleSessionSelect);
            
            // Booking form
            $(document).on('submit', '.bil24-booking-form', this.handleBookingSubmit);
            
            // Load more events (if pagination implemented)
            $(document).on('click', '.bil24-load-more', this.handleLoadMore);
            
            // Refresh sessions
            $(document).on('click', '.bil24-refresh-sessions', this.loadEventSessions);
            
            // Real-time availability checks
            this.startAvailabilityTimer();
        },

        /**
         * Initialize filters
         */
        initFilters: function() {
            // Set active view button
            var currentView = this.getCurrentView();
            $('.bil24-view-btn[data-view="' + currentView + '"]').addClass('active');
            
            // Apply current view to events list
            this.applyView(currentView);
            
            // Auto-submit filters on input change (with debounce)
            var debounceTimer;
            $('.bil24-filters-form input, .bil24-filters-form select').on('change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    $('.bil24-filters-form').trigger('submit');
                }, 500);
            });
        },

        /**
         * Load sessions for current event (single event pages)
         */
        loadSessionsForCurrentEvent: function() {
            var $container = $('.bil24-sessions-container');
            if ($container.length && $container.data('event-id')) {
                this.loadEventSessions($container.data('event-id'), $container);
            }
        },

        /**
         * Initialize booking interface
         */
        initBookingInterface: function() {
            // Setup booking steps
            this.resetBookingInterface();
            
            // Load sessions for booking
            var $bookingForm = $('.bil24-booking-form');
            if ($bookingForm.length) {
                var eventId = $bookingForm.data('event-id');
                if (eventId) {
                    this.loadSessionsForBooking(eventId);
                }
            }
        },

        /**
         * Handle view toggle
         */
        handleViewToggle: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var view = $btn.data('view');
            
            // Update active button
            $('.bil24-view-btn').removeClass('active');
            $btn.addClass('active');
            
            // Apply view
            Bil24Events.applyView(view);
            
            // Save preference
            Bil24Events.saveViewPreference(view);
        },

        /**
         * Handle filter form submission
         */
        handleFilterSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            // Show loading
            Bil24Events.showLoadingInContainer('.bil24-events-list');
            
            // Submit via AJAX
            $.post(bil24_ajax.url, {
                action: 'bil24_filter_events',
                nonce: bil24_ajax.nonce,
                filters: formData
            })
            .done(function(response) {
                if (response.success) {
                    $('.bil24-events-list').html(response.data.html);
                    
                    // Apply current view
                    var currentView = Bil24Events.getCurrentView();
                    Bil24Events.applyView(currentView);
                    
                    // Update URL without reload
                    if (history.pushState) {
                        var newUrl = window.location.pathname + '?' + formData;
                        history.pushState({}, '', newUrl);
                    }
                } else {
                    Bil24Events.showError(response.data || bil24_ajax.messages.booking_error);
                }
            })
            .fail(function() {
                Bil24Events.showError(bil24_ajax.messages.booking_error);
            });
        },

        /**
         * Handle quick book button
         */
        handleQuickBook: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var eventId = $btn.data('event-id');
            
            // Open booking modal or navigate to event page
            if (typeof bil24_modal !== 'undefined') {
                Bil24Events.openBookingModal(eventId);
            } else {
                // Navigate to event page
                var eventUrl = $btn.closest('.bil24-event-item').find('.bil24-event-title a').attr('href');
                if (eventUrl) {
                    window.location.href = eventUrl + '#booking';
                }
            }
        },

        /**
         * Handle session selection
         */
        handleSessionSelect: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var sessionId = $btn.data('session-id');
            
            // Update UI
            $('.bil24-session-item').removeClass('selected');
            $btn.closest('.bil24-session-item').addClass('selected');
            
            // Load ticket options for this session
            Bil24Events.loadTicketOptions(sessionId);
            
            // Show next step
            Bil24Events.showBookingStep('tickets');
        },

        /**
         * Handle booking form submission
         */
        handleBookingSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = new FormData($form[0]);
            formData.append('action', 'bil24_add_to_cart');
            formData.append('nonce', bil24_ajax.nonce);
            
            // Disable submit button
            var $submitBtn = $form.find('.bil24-add-to-cart');
            $submitBtn.prop('disabled', true).text(bil24_ajax.messages.loading);
            
            // Submit booking
            $.ajax({
                url: bil24_ajax.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    Bil24Events.showSuccess(bil24_ajax.messages.tickets_added);
                    
                    // Update cart (if WooCommerce fragments are available)
                    if (typeof wc_cart_fragments_params !== 'undefined') {
                        $(document.body).trigger('wc_fragment_refresh');
                    }
                    
                    // Reset form
                    Bil24Events.resetBookingInterface();
                } else {
                    Bil24Events.showError(response.data || bil24_ajax.messages.booking_error);
                }
            })
            .fail(function() {
                Bil24Events.showError(bil24_ajax.messages.booking_error);
            })
            .always(function() {
                $submitBtn.prop('disabled', false).text(__('Add to Cart', 'bil24'));
            });
        },

        /**
         * Load event sessions
         */
        loadEventSessions: function(eventId, $container) {
            if (!eventId) {
                eventId = $(this).data('event-id') || $('.bil24-sessions-container').data('event-id');
            }
            
            if (!$container) {
                $container = $('.bil24-sessions-container');
            }
            
            if (!eventId || !$container.length) {
                return;
            }
            
            $container.html('<div class="bil24-loading">' + bil24_ajax.messages.loading + '</div>');
            
            $.post(bil24_ajax.url, {
                action: 'bil24_get_event_sessions',
                nonce: bil24_ajax.nonce,
                event_id: eventId
            })
            .done(function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                    $container.addClass('bil24-fade-in');
                } else {
                    $container.html('<p class="bil24-no-sessions">' + (response.data || bil24_ajax.messages.no_sessions) + '</p>');
                }
            })
            .fail(function() {
                $container.html('<p class="bil24-no-sessions">' + bil24_ajax.messages.no_sessions + '</p>');
            });
        },

        /**
         * Load sessions for booking interface
         */
        loadSessionsForBooking: function(eventId) {
            var $selector = $('.bil24-session-selector');
            
            $selector.html('<div class="bil24-loading">' + bil24_ajax.messages.loading + '</div>');
            
            $.post(bil24_ajax.url, {
                action: 'bil24_get_event_sessions',
                nonce: bil24_ajax.nonce,
                event_id: eventId,
                for_booking: true
            })
            .done(function(response) {
                if (response.success) {
                    $selector.html(response.data.html);
                    Bil24Events.showBookingStep('sessions');
                } else {
                    $selector.html('<p class="bil24-no-sessions">' + (response.data || bil24_ajax.messages.no_sessions) + '</p>');
                }
            })
            .fail(function() {
                $selector.html('<p class="bil24-no-sessions">' + bil24_ajax.messages.no_sessions + '</p>');
            });
        },

        /**
         * Load ticket options for selected session
         */
        loadTicketOptions: function(sessionId) {
            var $selector = $('.bil24-ticket-selector');
            
            $selector.html('<div class="bil24-loading">' + bil24_ajax.messages.loading + '</div>');
            
            $.post(bil24_ajax.url, {
                action: 'bil24_get_session_tickets',
                nonce: bil24_ajax.nonce,
                session_id: sessionId
            })
            .done(function(response) {
                if (response.success) {
                    $selector.html(response.data.html);
                    Bil24Events.updateBookingSummary(response.data.session);
                } else {
                    $selector.html('<p class="bil24-error">' + (response.data || bil24_ajax.messages.booking_error) + '</p>');
                }
            })
            .fail(function() {
                $selector.html('<p class="bil24-error">' + bil24_ajax.messages.booking_error + '</p>');
            });
        },

        /**
         * Show booking step
         */
        showBookingStep: function(step) {
            $('.bil24-booking-step').hide().removeClass('active');
            $('.bil24-step-' + step).show().addClass('active');
            
            if (step === 'summary') {
                this.generateBookingSummary();
            }
        },

        /**
         * Reset booking interface
         */
        resetBookingInterface: function() {
            $('.bil24-booking-step').hide().removeClass('active');
            $('.bil24-step-sessions').show().addClass('active');
            $('.bil24-session-item').removeClass('selected');
            $('.bil24-ticket-selector, .bil24-booking-summary').empty();
        },

        /**
         * Update booking summary
         */
        updateBookingSummary: function(sessionData) {
            var $summary = $('.bil24-booking-summary');
            var html = '<div class="bil24-summary-session">';
            html += '<h5>' + __('Selected Session', 'bil24') + '</h5>';
            html += '<p>' + sessionData.date + ' at ' + sessionData.time + '</p>';
            if (sessionData.venue) {
                html += '<p>' + sessionData.venue + '</p>';
            }
            html += '</div>';
            
            $summary.html(html);
        },

        /**
         * Generate complete booking summary
         */
        generateBookingSummary: function() {
            // This would collect all selected options and generate final summary
            // Implementation depends on ticket selection interface
            this.showBookingStep('summary');
        },

        /**
         * Apply view to events list
         */
        applyView: function(view) {
            var $list = $('.bil24-events-list');
            $list.removeClass('bil24-view-grid bil24-view-list bil24-view-card');
            $list.addClass('bil24-view-' + view);
        },

        /**
         * Get current view preference
         */
        getCurrentView: function() {
            return localStorage.getItem('bil24_events_view') || 'grid';
        },

        /**
         * Save view preference
         */
        saveViewPreference: function(view) {
            localStorage.setItem('bil24_events_view', view);
        },

        /**
         * Start availability timer for real-time updates
         */
        startAvailabilityTimer: function() {
            // Check availability every 30 seconds
            setInterval(function() {
                Bil24Events.checkAvailability();
            }, 30000);
        },

        /**
         * Check ticket availability
         */
        checkAvailability: function() {
            var sessionIds = [];
            
            $('.bil24-session-item').each(function() {
                var sessionId = $(this).data('session-id');
                if (sessionId) {
                    sessionIds.push(sessionId);
                }
            });
            
            if (sessionIds.length === 0) {
                return;
            }
            
            $.post(bil24_ajax.url, {
                action: 'bil24_check_availability',
                nonce: bil24_ajax.nonce,
                session_ids: sessionIds.join(',')
            })
            .done(function(response) {
                if (response.success && response.data) {
                    Bil24Events.updateAvailabilityDisplay(response.data);
                }
            });
        },

        /**
         * Update availability display
         */
        updateAvailabilityDisplay: function(availabilityData) {
            $.each(availabilityData, function(sessionId, data) {
                var $item = $('.bil24-session-item[data-session-id="' + sessionId + '"]');
                var $availability = $item.find('.bil24-session-availability');
                var $button = $item.find('.bil24-select-session');
                
                if (data.available > 0) {
                    $availability.html('<span class="bil24-available">' + sprintf(__('%d tickets available', 'bil24'), data.available) + '</span>');
                    $button.prop('disabled', false).text(__('Select', 'bil24')).removeClass('bil24-btn-disabled').addClass('bil24-btn-primary');
                } else {
                    $availability.html('<span class="bil24-sold-out">' + __('Sold Out', 'bil24') + '</span>');
                    $button.prop('disabled', true).text(__('Sold Out', 'bil24')).removeClass('bil24-btn-primary').addClass('bil24-btn-disabled');
                }
            });
        },

        /**
         * Show loading in container
         */
        showLoadingInContainer: function(selector) {
            $(selector).html('<div class="bil24-loading">' + bil24_ajax.messages.loading + '</div>');
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.showNotification(message, 'error');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Create notification element
            var $notification = $('<div class="bil24-notification bil24-notification-' + type + '">' + message + '</div>');
            
            // Add to page
            $('body').append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.addClass('bil24-notification-show');
            }, 100);
            
            // Auto remove
            setTimeout(function() {
                $notification.removeClass('bil24-notification-show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 5000);
        },

        /**
         * Handle load more events
         */
        handleLoadMore: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var page = $btn.data('page') || 2;
            
            $btn.text(bil24_ajax.messages.loading).prop('disabled', true);
            
            $.post(bil24_ajax.url, {
                action: 'bil24_load_more_events',
                nonce: bil24_ajax.nonce,
                page: page,
                filters: $('.bil24-filters-form').serialize()
            })
            .done(function(response) {
                if (response.success && response.data.html) {
                    $('.bil24-events-list').append(response.data.html);
                    
                    if (response.data.has_more) {
                        $btn.data('page', page + 1).text(__('Load More', 'bil24')).prop('disabled', false);
                    } else {
                        $btn.remove();
                    }
                } else {
                    $btn.remove();
                }
            })
            .fail(function() {
                $btn.text(__('Load More', 'bil24')).prop('disabled', false);
            });
        },

        /**
         * Open booking modal (if modal system is available)
         */
        openBookingModal: function(eventId) {
            // This would open a modal with the booking interface
            // Implementation depends on modal system
            console.log('Opening booking modal for event:', eventId);
        }
    };

    /**
     * Utility functions
     */
    
    // Simple translation function fallback
    if (typeof __ === 'undefined') {
        window.__ = function(text, domain) {
            return text;
        };
    }
    
    // Simple sprintf fallback
    if (typeof sprintf === 'undefined') {
        window.sprintf = function(format) {
            var args = Array.prototype.slice.call(arguments, 1);
            return format.replace(/%[sd%]/g, function(match) {
                return args.shift();
            });
        };
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        Bil24Events.init();
    });

    // Make Bil24Events globally available
    window.Bil24Events = Bil24Events;

})(jQuery); 