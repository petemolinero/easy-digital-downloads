<?php

function edd_checkout_form() {

	global $edd_options, $user_ID, $post;
	
	if (is_singular()) :
		$page_URL =  get_permalink($post->ID);
	else :
		$page_URL = 'http';
		if ($_SERVER["HTTPS"] == "on") $pageURL .= "s";
		$page_URL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else $page_URL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	endif;	
	
	if(is_user_logged_in()) :
		global $user_ID;
		$user_data = get_userdata($user_ID);
	endif;
	
	ob_start(); ?>
		
		<?php if(edd_get_cart_contents()) : ?>
				
			<?php echo edd_checkout_cart(); ?>
			
			<div id="edd_checkout_form_wrap">
			
				<?php if(!isset($_GET['payment-mode'])) { ?>
					<?php do_action('edd_payment_mode_top'); ?>
					<form id="edd_payment_mode" action="<?php echo $page_URL; ?>" method="GET">
						<fieldset>
							<?php do_action('edd_payment_mode_before_gateways'); ?>
							<p>
								<?php
								$gateways = edd_get_enabled_payment_gateways();
								if(count($gateways) > 1) {
									echo '<select class="edd-select" name="payment-mode" id="edd-gateway">';
										foreach($gateways as $gateway_id => $gateway) :
											echo '<option value="' . $gateway_id . '">' . $gateway['checkout_label'] . '</option>';
										endforeach;
									echo '</select>';
									echo '<label for="edd-gateway">' . __('Choose Your Payment Method', 'rcp') . '</label>';
								} else {
									foreach($gateways as $gateway_id => $gateway) :
										echo '<input type="hidden" name="payment-mode" value="' . $gateway_id . '"/>';
									endforeach;
								}
								?>
							</p>
							<?php do_action('edd_payment_mode_after_gateways'); ?>
						</fieldset>
						<fieldset>
							<p>
								<input type="submit" value="<?php _e('Next', 'edd'); ?>"/>
							</p>
						</fieldset>
					</form>
					<?php do_action('edd_payment_mode_bottom'); ?>
			
				<?php } else { ?>
					
					<form id="edd_purchase_form" action="<?php echo $page_URL; ?>" method="POST">					
					
						<?php do_action('edd_before_purchase_form'); ?>
					
						<?php if(isset($edd_options['logged_in_only']) && !is_user_logged_in() && !isset($_GET['login'])) { ?>
							<div id="edd_checkout_login_register"><?php echo edd_get_register_fields(); ?></div>
						<?php } elseif(isset($edd_options['show_register_form']) && !is_user_logged_in() && isset($_GET['login'])) { ?>
							<div id="edd_checkout_login_register"><?php echo edd_get_login_fields(); ?></div>
						<?php } ?>
						<?php if(!isset($_GET['login']) && is_user_logged_in()) { ?>											
						<fieldset>
							<p>
								<input class="edd-input" type="text" name="edd-email" id="edd-email" value="<?php echo is_user_Logged_in() ? $user_data->user_email : ''; ?>"/>
								<label class="edd-label" for="edd-email"><?php _e('Email Address', 'edd'); ?></label>
							</p>
							<p>
								<input class="edd-input" type="text" name="edd-first" id="edd-first" value="<?php echo is_user_Logged_in() ? $user_data->user_firstname : ''; ?>"/>
								<label class="edd-label" for="edd-first"><?php _e('First Name', 'edd'); ?></label>
							</p>
							<p>
								<input class="edd-input" type="text" name="edd-last" id="edd-last" value="<?php echo is_user_Logged_in() ? $user_data->user_lastname : ''; ?>"/>
								<label class="edd-label" for="edd-last"><?php _e('Last Name', 'edd'); ?></label>
							</p>	
							<?php do_action('edd_purchase_form_user_info'); ?>
						</fieldset>				
						<?php } ?>		
						<fieldset>
							<p>
								<input class="edd-input" type="text" id="edd-discount" name="edd-discount"/>
								<label class="edd-label" for="edd-discount">
									<?php _e('Discount', 'edd'); ?>
									<?php if(edd_is_ajax_enabled()) { ?>
										- <a href="#" class="edd-apply-discount"><?php _e('Apply Discount', 'edd'); ?></a>
									<?php } ?>
								</label>
							</p>
						</fieldset>				
						<?php 
							// load the credit card form and allow gateways to load their own if they wish
							if(has_action('edd_' . urldecode($_GET['payment-mode']) . '_cc_form')) {
								do_action('edd_' . urldecode($_GET['payment-mode']) . '_cc_form'); 
							} else {
								do_action('edd_cc_form');
							}
						?>					
						<fieldset>
							<p>
								<?php do_action('edd_purchase_form_before_submit'); ?>
								<?php if(is_user_logged_in()) { ?>
								<input type="hidden" name="edd-user-id" value="<?php echo $user_data->ID; ?>"/>
								<?php } ?>
								<input type="hidden" name="edd-action" value="purchase"/>
								<input type="hidden" name="edd-gateway" value="<?php echo urldecode($_GET['payment-mode']); ?>" />
								<input type="hidden" name="edd-nonce" value="<?php echo wp_create_nonce('edd-purchase-nonce'); ?>"/>
								<input type="submit" name="edd-purchase" value="<?php _e('Purchase', 'edd'); ?>"/>
								<?php do_action('edd_purchase_form_after_submit'); ?>
							</p>
							<?php if(!edd_is_ajax_enabled()) { ?>
								<p class="edd-cancel"><a href="javascript:history.go(-1)"><?php _e('Go back', 'edd'); ?></a></p>
							<?php } ?>				
						</fieldset>
						<?php do_action('edd_purchase_form_bottom'); ?>
					</form>
					<?php do_action('edd_after_purchase_form'); ?>
			<?php } ?>
		</div><!--end #edd_checkout_form_wrap-->
		<?php
		else:
			do_action('edd_empty_cart');
		endif;
	return ob_get_clean();
}

function edd_get_cc_form() {
	ob_start(); ?>
	
	<?php do_action('edd_before_cc_fields'); ?>
	<fieldset>
		<p>
			<input type="text" autocomplete="off" name="card_name" class="card-name edd-input required" />
			<label class="edd-label"><?php _e('Name on the Card', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" autocomplete="off" name="card_number" class="card-number edd-input required" />
			<label class="edd-label"><?php _e('Card Number', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" size="4" autocomplete="off" name="card_cvc" class="card-cvc edd-input required" />
			<label class="edd-label"><?php _e('CVC', 'edd'); ?></label>
		</p>
		<?php do_action('edd_before_cc_expiration'); ?>
		<p class="card-expiration">
			<input type="text" size="2" name="card_exp_month" class="card-expiry-month edd-input required"/>
			<span class="exp-divider"> / </span>
			<input type="text" size="4" name="card_exp_year" class="card-expiry-year edd-input required"/>
			<label class="edd-label"><?php _e('Expiration (MM/YYYY)', 'edd'); ?></label>
		</p>
		<?php do_action('edd_before_cc_expiration'); ?>
	</fieldset>

	<?php do_action('edd_cc_form_address_fields', 'edd_default_cc_address_fields'); ?>

	<?php do_action('edd_after_cc_fields'); ?>
		
	<?php
	echo ob_get_clean();
}
add_action('edd_cc_form', 'edd_get_cc_form');

// outputs the default credit card address fields
function edd_default_cc_address_fields() {
	ob_start(); ?>
	<fieldset class="cc-address">
		<p>
			<input type="text" name="card_address" class="card-address edd-input required"/>
			<label class="edd-label"><?php _e('Billing Address', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="card_address_2" class="card-address-2 edd-input required"/>
			<label class="edd-label"><?php _e('Billing Address Line 2', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="card_city" class="card-city edd-input required"/>
			<label class="edd-label"><?php _e('Billing City', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" size="6" name="card_state" class="card-state edd-input required"/>
			<label class="edd-label"><?php _e('Billing State / Province', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" size="4" name="card_zip" class="card-zip edd-input required"/>
			<label class="edd-label"><?php _e('Billing Zip / Postal Code', 'edd'); ?></label>
		</p>
	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action('edd_cc_form_address_fields', 'edd_default_cc_address_fields');

function edd_get_register_fields() {
	ob_start(); ?>
	<fieldset id="edd_register_fields">
		<legend><?php _e('Create an account', 'edd'); ?></legend>
		<p>
			<input name="edd_user_login" id="edd_user_login" class="required edd-input" type="text" title="<?php _e('Username', 'edd'); ?>"/>
			<label for="edd_user_Login"><?php _e('Username', 'edd'); ?></label>
		</p>
		<p>
			<input name="edd_user_email" id="edd_user_email" class="required edd-input" type="email" title="<?php _e('email@domain.com', 'edd'); ?>"/>
			<label for="edd_user_email"><?php _e('Email', 'edd'); ?></label>
		</p>
		<p>
			<input name="edd_user_pass" id="edd_user_pass" class="required edd-input" type="password"/>
			<label for="password"><?php _e('Password', 'edd'); ?></label>
		</p>
		<p>
			<input name="edd_user_pass_confirm" id="edd_user_pass_confirm" class="required edd-input" type="password"/>
			<label for="password_again"><?php _e('Password Again', 'edd'); ?></label>
		</p>
		<p><?php _e('Already have an account?', 'edd'); ?> <a href="<?php echo add_query_arg('login', 1); ?>" class="edd_checkout_register_login" data-action="checkout_login"><?php _e('Login', 'edd'); ?></a></p>
		<input type="hidden" name="edd-purchase-var" value="needs-to-register"/>		
		<?php do_action('edd_purchase_form_register_fields'); ?>								
	</fieldset>
	<?php
	return ob_get_clean();
}

function edd_get_login_fields() {
	ob_start(); ?>
		<fieldset id="edd_login_fields">
			<legend><?php _e('Login to your account', 'edd'); ?></legend>
			<p>
				<input class="edd-input" type="text" name="edd-username" id="edd-username" value="" placeholder="<?php _e('Your username', 'edd'); ?>"/>
				<label class="edd-label" for="edd-username"><?php _e('Username', 'edd'); ?></label>
			</p>
			<p>
				<input class="edd-input" type="password" name="edd-password" id="edd-password" placeholder="<?php _e('Your password', 'edd'); ?>"/>
				<label class="edd-label" for="edd-password"><?php _e('Password', 'edd'); ?></label>
				<input type="hidden" name="edd-purchase-var" value="needs-to-login"/>
			</p>
			<p><?php _e('Need to create an account?', 'edd'); ?> <a href="<?php echo remove_query_arg('login'); ?>" class="edd_checkout_register_login" data-action="checkout_register"><?php _e('Register', 'edd'); ?></a></p>
			
			<?php do_action('edd_purchase_form_login_fields'); ?>		
		</fieldset><!--end #edd_login_fields-->
	<?php
	return ob_get_clean();
}