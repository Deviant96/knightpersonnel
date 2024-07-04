# According to Flow

## Login
 - Ajax function to check whether visitor is logged in or not in check_user_login then both condition will print string
 - Redirection for user based on their role with condition for administrator, editor, subscriber, and else other roles.
 - Add an Ajax function to check whether user logged in or not. Then will return a boolean JSON result via logged_in for which the variable can be accessed in Javasript, in custom_user_check.

 - Billing Country field will be hidden in checkout page in hide_billing_country_field

 - The thank you page is customized to redirect to amstraining URL using wp_safe_redirect in custom_thankyou_redirect

 - Intent to change order status to completed when payment is already received but got commented out on custom_auto_complete_order 


- Customized checkout fields set in custom_override_checkout_fields to modify checkout elements such as label, placeholder, and class
- Extra text information added before the checkout form about user that should provide entry requirements, registration details and make payments in woocommerce_checkout_login_form.

## Cart
 - User prevented from going to checkout if their total cart is zero by removing checkout button and adding custom message in prevent_checkout_for_zero_subtotal and zero_subtotal_checkout_message

## Checkout
 - Scripts added to footer to remove attributes such as placeholder and screen-reader-text for billing fields in add_custom_script_to_footer
 - Add custom fields about student details in checkout before customer details in custom_checkout_fields_before_billing_details

## Admin
 - Intent to hide dashboard/Deny access for role subscriber in custom_hide_dashboard by redirecting them to homepage
 - Hiding Wordpress admin bar for user with role purchase_order_client in custom_hide_admin_bar_based_on_role

## Custom Payment
 - Add enqueue for Stripe payment scripts in enqueue_stripe_scripts that uses custom publishable_key
 - Add custom error message when Stripe payment is error then update order status to failed in display_custom_error_message

## Order Completed
 - 

# Additional Details
 - Removing default Wordpress Jquery and adding custom Jquery 3.6.0
 - List of enqueue of stylesheets for stylings
 - A couple of defined hex colors in impeka_child_colors
 - Font awesome script called in custom_code_in_header
 - Bootstrap bundle, SwiperJS, Font Awesome, and calendar script defined in shortcode function calendar_shortcut_shortcode
 - Replacing text to empty string in bbloomer_translate_woocommerce_strings_emails
 - A shortcode to include calendar module with its stylings including the necessary scripts like Bootstrap Bundles, Font Awesome, Jquery, Typekit, amd widget script from rtodata.com.au
 - A new user role Purchase Order Client defined in create_custom_role which has capability of read and edit posts
 - Google Tag Manager script added to head in add_google_tag_manager
 - Google Tag Manager script (empty) added to footer in add_google_tag_manager_footer
 - Extra functions: 
    - Custom shortcode custom_hello showing greetings
    - my_action_callback which prints string QWERTY
    - empty function impeka_child_theme_setup
    - empty function my_custom_action_after_order_completion which runs when an order status changed to completed