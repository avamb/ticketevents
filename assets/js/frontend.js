/**
 * Bil24 Frontend JavaScript
 * Handles ticket reservations, cart interactions, and real-time availability checks
 */

(function($) {
    'use strict';

    var Bil24Frontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.initReservationTimer();
            this.initAvailabilityChecker();
            this.initCartFragments();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Add to cart validation
            $(document).on('click', '.single_add_to_cart_button', this.validateAddToCart);
            
            // Quantity changes
            $(document).on('change', 'input.qty', this.checkAvailabilityOnQuantityChange);
            
            // Extend reservation buttons
            $(document).on('click', '#bil24-extend-reservation', this.extendReservation);
            
            // Cart updates
            $(document).on('updated_cart_totals', this.onCartUpdated);
            $(document).on('updated_checkout', this.onCheckoutUpdated);
            
            // Product page interactions
            $(document).on('change', '.variations select', this.onVariationChange);
            
            // Checkout validation
            $(document).on('checkout_place_order', this.validateCheckout);
        },

        /**
         * Validate add to cart action
         */
        validateAddToCart: function(e) {
            var $button = $(this);
            var $form = $button.closest('form.cart');
            var productId = $form.find('input[name="add-to-cart"]').val() || $button.val();
            var quantity = $form.find('input[name="quantity"]').val() || 1;
            
            // Check if this is a Bil24 product
            if (!$button.data('bil24-product')) {
                return true;
            }
            
            e.preventDefault();
            
            // Show loading state
            $button.addClass('loading').text(bil24_frontend.strings.adding_to_cart);
            
            // Check availability
            Bil24Frontend.checkAvailability(productId, quantity, function(available, data) {
                $button.removeClass('loading').text($button.data('original-text') || 'Add to cart');
                
                if (available) {
                    // Proceed with add to cart
                    $form.off('submit').submit();
                } else {
                    Bil24Frontend.showUnavailableMessage(data);
                }
            });
            
            return false;
        },

        /**
         * Check availability on quantity change
         */
        checkAvailabilityOnQuantityChange: function() {
            var $input = $(this);
            var quantity = parseInt($input.val());
            var productId = $input.closest('form').find('input[name="add-to-cart"]').val();
            
            if (!productId || !$input.data('bil24-product')) {
                return;
            }
            
            clearTimeout($input.data('availability-timeout'));
            
            var timeout = setTimeout(function() {
                Bil24Frontend.checkAvailability(productId, quantity, function(available, data) {
                    Bil24Frontend.updateAvailabilityDisplay(available, data, $input);
                });
            }, 500);
            
            $input.data('availability-timeout', timeout);
        },

        /**
         * Check ticket availability via AJAX
         */
        checkAvailability: function(productId, quantity, callback) {
            $.ajax({
                url: bil24_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'bil24_check_availability',
                    product_id: productId,
                    quantity: quantity,
                    nonce: bil24_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data.available, response.data);
                    } else {
                        callback(false, { message: response.data.message });
                    }
                },
                error: function() {
                    callback(false, { message: 'Network error' });
                }
            });
        },

        /**
         * Update availability display
         */
        updateAvailabilityDisplay: function(available, data, $context) {
            var $availabilityDiv = $context.siblings('.bil24-availability') || 
                                  $context.closest('.product').find('.bil24-availability');
            
            if (!$availabilityDiv.length) {
                $availabilityDiv = $('<div class="bil24-availability"></div>');
                $context.after($availabilityDiv);
            }
            
            $availabilityDiv.removeClass('available unavailable');
            
            if (available) {
                $availabilityDiv.addClass('available')
                    .html('<span class="availability-icon">✓</span> Доступно: ' + data.available_tickets + ' билетов');
            } else {
                $availabilityDiv.addClass('unavailable')
                    .html('<span class="availability-icon">✗</span> ' + (data.message || 'Недоступно'));
            }
        },

        /**
         * Show unavailable message
         */
        showUnavailableMessage: function(data) {
            var message = data.message || bil24_frontend.strings.tickets_not_available;
            
            // Create or update notice
            var $notice = $('.bil24-availability-notice');
            if (!$notice.length) {
                $notice = $('<div class="bil24-availability-notice woocommerce-error"></div>');
                $('.single-product .summary').prepend($notice);
            }
            
            $notice.html(message).show();
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        },

        /**
         * Initialize reservation timer
         */
        initReservationTimer: function() {
            var $countdown = $('#bil24-countdown');
            if (!$countdown.length) return;
            
            this.startCountdownTimer($countdown);
        },

        /**
         * Start countdown timer
         */
        startCountdownTimer: function($countdown) {
            var self = this;
            var timeLeft = parseInt($countdown.data('time-left')) || 0;
            
            if (timeLeft <= 0) {
                this.onReservationExpired();
                return;
            }
            
            var timer = setInterval(function() {
                timeLeft--;
                
                var minutes = Math.floor(timeLeft / 60);
                var seconds = timeLeft % 60;
                
                $countdown.text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);
                
                // Warning when less than 2 minutes left
                if (timeLeft <= 120) {
                    $countdown.addClass('warning');
                }
                
                // Critical when less than 30 seconds left
                if (timeLeft <= 30) {
                    $countdown.addClass('critical');
                }
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    self.onReservationExpired();
                }
            }, 1000);
            
            // Store timer reference for cleanup
            $countdown.data('timer', timer);
        },

        /**
         * Handle reservation expiration
         */
        onReservationExpired: function() {
            // Show expiration notice
            var $notice = $('<div class="bil24-reservation-expired woocommerce-error">' +
                bil24_frontend.strings.reservation_expired + 
                '</div>');
            
            $('.cart-collaterals, .checkout-form').prepend($notice);
            
            // Refresh page after short delay
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        },

        /**
         * Extend reservation
         */
        extendReservation: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Продление...').prop('disabled', true);
            
            $.ajax({
                url: bil24_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'bil24_extend_reservation',
                    nonce: bil24_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show updated timer
                        window.location.reload();
                    } else {
                        alert('Не удалось продлить резервирование');
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Ошибка сети');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Initialize availability checker for product pages
         */
        initAvailabilityChecker: function() {
            var $productForm = $('form.cart');
            if (!$productForm.length) return;
            
            var productId = $productForm.find('input[name="add-to-cart"]').val();
            if (!productId) return;
            
            // Check if this is a Bil24 product
            var $addToCartButton = $productForm.find('.single_add_to_cart_button');
            if ($addToCartButton.data('bil24-product')) {
                this.setupRealTimeAvailability(productId);
            }
        },

        /**
         * Setup real-time availability checking
         */
        setupRealTimeAvailability: function(productId) {
            var self = this;
            
            // Initial check
            this.checkAvailability(productId, 1, function(available, data) {
                self.updateProductAvailability(available, data);
            });
            
            // Periodic checks every 30 seconds
            setInterval(function() {
                var quantity = $('input[name="quantity"]').val() || 1;
                self.checkAvailability(productId, quantity, function(available, data) {
                    self.updateProductAvailability(available, data);
                });
            }, 30000);
        },

        /**
         * Update product availability display
         */
        updateProductAvailability: function(available, data) {
            var $summary = $('.product .summary');
            var $availability = $summary.find('.bil24-real-time-availability');
            
            if (!$availability.length) {
                $availability = $('<div class="bil24-real-time-availability"></div>');
                $summary.find('.price').after($availability);
            }
            
            $availability.removeClass('available unavailable low-stock');
            
            if (available) {
                var availableTickets = data.available_tickets;
                
                if (availableTickets > 10) {
                    $availability.addClass('available')
                        .html('<span class="stock-icon">✓</span> В наличии');
                } else if (availableTickets > 0) {
                    $availability.addClass('low-stock')
                        .html('<span class="stock-icon">⚠</span> Осталось: ' + availableTickets + ' билетов');
                } else {
                    $availability.addClass('unavailable')
                        .html('<span class="stock-icon">✗</span> Распродано');
                }
            } else {
                $availability.addClass('unavailable')
                    .html('<span class="stock-icon">✗</span> Недоступно');
            }
        },

        /**
         * Initialize cart fragments for AJAX updates
         */
        initCartFragments: function() {
            // Listen for cart fragment updates
            $(document.body).on('updated_wc_div', this.onCartFragmentUpdate);
        },

        /**
         * Handle cart fragment updates
         */
        onCartFragmentUpdate: function() {
            // Reinitialize timer if needed
            Bil24Frontend.initReservationTimer();
        },

        /**
         * Handle cart updates
         */
        onCartUpdated: function() {
            // Check if any reservations expired
            Bil24Frontend.checkCartReservations();
        },

        /**
         * Handle checkout updates
         */
        onCheckoutUpdated: function() {
            // Validate reservations before allowing checkout
            Bil24Frontend.validateCheckoutReservations();
        },

        /**
         * Check cart reservations
         */
        checkCartReservations: function() {
            // This will be called after cart updates
            // Server-side validation will handle expired reservations
        },

        /**
         * Validate checkout reservations
         */
        validateCheckoutReservations: function() {
            var $checkout = $('form.checkout');
            if (!$checkout.length) return;
            
            // Look for reservation warnings
            var $reservationNotice = $('.bil24-reservation-notice');
            if ($reservationNotice.length) {
                // Scroll to reservation notice
                $('html, body').animate({
                    scrollTop: $reservationNotice.offset().top - 100
                }, 500);
            }
        },

        /**
         * Validate checkout before submission
         */
        validateCheckout: function() {
            // Server-side validation will handle this
            // Just show loading state
            $('.bil24-reservation-notice').find('button').prop('disabled', true);
        },

        /**
         * Handle variation changes
         */
        onVariationChange: function() {
            var $select = $(this);
            var $form = $select.closest('form.cart');
            var productId = $form.find('input[name="add-to-cart"]').val();
            
            if (!productId) return;
            
            // Reset availability display
            $('.bil24-availability').remove();
            
            // Check availability for new variation
            setTimeout(function() {
                var quantity = $form.find('input[name="quantity"]').val() || 1;
                Bil24Frontend.checkAvailability(productId, quantity, function(available, data) {
                    Bil24Frontend.updateAvailabilityDisplay(available, data, $form.find('input[name="quantity"]'));
                });
            }, 500);
        },

        /**
         * Utility: Format time
         */
        formatTime: function(seconds) {
            var minutes = Math.floor(seconds / 60);
            var remainingSeconds = seconds % 60;
            return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
        },

        /**
         * Utility: Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="bil24-notification bil24-notification-' + type + '">' +
                '<span class="notification-message">' + message + '</span>' +
                '<button class="notification-close">&times;</button>' +
                '</div>');
            
            $('body').append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.find('.notification-close').on('click', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Utility: Debug logging
         */
        log: function(message, data) {
            if (window.console && window.console.log) {
                console.log('[Bil24Frontend]', message, data || '');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        Bil24Frontend.init();
    });

    // Expose to global scope for external access
    window.Bil24Frontend = Bil24Frontend;

})(jQuery); 