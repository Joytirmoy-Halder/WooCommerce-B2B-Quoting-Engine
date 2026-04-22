jQuery(document).ready(function($) {
	// Initialize Float Badge count independently of cached HTML
	if ($('#b2b-floating-cart').length) {
		$.ajax({
			url: b2b_quote_params.ajax_url,
			type: 'GET',
			cache: false,
			data: { action: 'b2b_get_quote_count' },
			success: function(response) {
				if (response.success && response.data.count > 0) {
					$('#b2b-floating-cart').css('display', 'flex');
					$('#b2b-floating-cart .b2b-cart-count').text(response.data.count);
				}
			}
		});
	}

	// Add to quote
	$(document).on('click', '.b2b-add-to-quote', function(e) {
		e.preventDefault();
		var button = $(this);
		var product_id = button.data('product_id');
		
		// Find quantity if it exists, default to 1
		var form = button.closest('form.cart');
		var quantity = 1;
		if (form.length && form.find('input.qty').length) {
			quantity = form.find('input.qty').val();
		}

		// Handle variable products
		var variation_id_input = form.find('input[name="variation_id"]');
		if (variation_id_input.length && variation_id_input.val() !== '' && variation_id_input.val() != 0) {
			product_id = variation_id_input.val();
		} else if (variation_id_input.length) {
			alert('Please select product options before adding to quote.');
			return;
		}

		button.addClass('loading');

		$.ajax({
			url: b2b_quote_params.ajax_url,
			type: 'POST',
			data: {
				action: 'b2b_add_to_quote',
				security: b2b_quote_params.nonce,
				product_id: product_id,
				quantity: quantity
			},
			success: function(response) {
				button.removeClass('loading');
				button.siblings('.b2b-quote-notice').remove();
				
				if (response.success) {
					var actionBtn = '';
					if (response.data.cart_url) {
						actionBtn = ' <a href="' + response.data.cart_url + '" style="margin-left: 10px; font-weight: bold; text-decoration: underline; color: #2e3a17;">View Quote Cart &rarr;</a>';
					}
					var messageHtml = '<div class="b2b-quote-notice" style="margin-top: 10px; padding: 10px; background: #e5f1ca; color: #516428; border-left: 4px solid #8fae1b; font-size: 14px; position:relative; z-index:99;">' + response.data.message + actionBtn + '</div>';
					button.after(messageHtml);
					
					// Floating Badge Update
					if ($('#b2b-floating-cart').length) {
						$('#b2b-floating-cart').css('display', 'flex').hide().fadeIn();
						$('#b2b-floating-cart .b2b-cart-count').text(response.data.count);
					}
				} else {
					var errorHtml = '<div class="b2b-quote-notice" style="margin-top: 10px; padding: 10px; background: #fce4e4; color: #cc0000; border-left: 4px solid #cc0000; font-size: 14px;">' + response.data.message + '</div>';
					button.after(errorHtml);
				}
				
				// Hide the notice after 5 seconds automatically
				setTimeout(function() {
					button.siblings('.b2b-quote-notice').fadeOut(300, function() { $(this).remove(); });
				}, 5000);
			}
		});
	});

	// Remove from quote
	$(document).on('click', '.b2b-remove-item', function(e) {
		e.preventDefault();
		var button = $(this);
		var product_id = button.data('product_id');

		$.ajax({
			url: b2b_quote_params.ajax_url,
			type: 'POST',
			data: {
				action: 'b2b_remove_from_quote',
				security: b2b_quote_params.nonce,
				product_id: product_id
			},
			success: function(response) {
				if(response.success) {
					button.closest('tr').fadeOut(300, function() {
						$(this).remove();
						if($('.b2b-quote-cart-table tbody tr').length === 0) {
							location.reload(); // Reload to show empty message
						}
					});
					
					// Floating Badge Update
					if (response.data.count !== undefined && $('#b2b-floating-cart').length) {
						$('#b2b-floating-cart .b2b-cart-count').text(response.data.count);
						if (response.data.count === 0) {
							$('#b2b-floating-cart').fadeOut();
						}
					}
				} else {
					alert(response.data ? response.data.message : 'Error removing item.');
				}
			},
			error: function() {
				alert('Server error while attempting to remove the item.');
			}
		});
	});

	// Submit Quote form
	$('#b2b-quote-submit-form').on('submit', function(e) {
		e.preventDefault();
		var form = $(this);
		var submitBtn = form.find('.b2b-submit-quote-btn');
		var notices = $('#b2b-quote-notices');

		submitBtn.prop('disabled', true).text('Submitting...');
		notices.html('');

		var data = {
			action: 'b2b_submit_quote',
			security: b2b_quote_params.nonce,
			client_name: form.find('#b2b_client_name').val(),
			client_email: form.find('#b2b_client_email').val(),
			client_company: form.find('#b2b_client_company').val(),
			technical_reqs: form.find('#b2b_technical_reqs').val(),
		};

		$.ajax({
			url: b2b_quote_params.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					notices.html('<div class="woocommerce-message">' + response.data.message + '</div>');
					form[0].reset();
					$('.b2b-quote-cart-table').fadeOut();
					form.find('p').fadeOut();
				} else {
					notices.html('<div class="woocommerce-error">' + response.data.message + '</div>');
					submitBtn.prop('disabled', false).text('Submit Request');
				}
			},
			error: function() {
				notices.html('<div class="woocommerce-error">An error occurred. Please try again.</div>');
				submitBtn.prop('disabled', false).text('Submit Request');
			}
		});
	});
});
