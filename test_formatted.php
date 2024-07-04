<?php

/**
 * Child Theme
 *
 */
/****** Custom Routing ********/
function sendEmail($order_id)
{
	$order = wc_get_order($order_id);
	$order_data = $order->get_data();
	$number_students = $order->get_meta('student_quantity');
	$course_name = $order->get_meta('course_name');
	$purchase_order = $order->get_meta('_payment_method');

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Cc: sales@amstraining.com.au', // CC recipient's email
		'From: AMS Training <info@amstraining.com.au>' // Sender's email
	);

	// Generate a random secret key
	$length = 10;
	$randomText = '';
	$characters = 'abcdefghijklmnopqrstuvwxyz';
	for ($i = 0; $i < $length; $i++) {
		$randomText .= $characters[rand(0, strlen($characters) - 1)];
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'valid_student_list';

	$data = array(
		'secret_key' => $randomText,
	);
	$customer_email = $order->get_billing_email();

	$wpdb->insert($table_name, $data);

	$list_students = "";
	for ($i = 1; $i <= $number_students; $i++) {
		$name_meta = 'Student_First_Name_' . $i;
		$name_meta = $order->get_meta($name_meta);
		$lastname_meta = 'Student_Last_Name_' . $i;
		$lastname_meta = $order->get_meta($lastname_meta);
		$email_meta = 'Student_Email_' . $i;
		$email_address = $order->get_meta($email_meta);
		$list_students .= $name_meta . " " . $lastname_meta . " " . $email_address . "<br>";

		// Generate a secret key for each student
		$length = 32;
		$secretKey = bin2hex(random_bytes($length));

		$table_name = $wpdb->prefix . 'secret_keys';
		$data = array(
			'secret_key' => $secretKey,
		);

		$result = $wpdb->insert($table_name, $data);
		if ($result !== false) {
			// Successfully inserted the secret key
		} else {
			// Failed to insert the secret key, handle error
		}
		$order = wc_get_order($order_id);

		foreach ($order->get_items() as $item_id => $item) {
			$enrolemnt_link = 'https://amstraining.com.au/enrolments/?' . $item->get_meta('enrolemnt_link');
			$course_name = $item->get_meta('course_name');
		}
		$link = "<br> Please use the following link to complete your enrollment" . " <a href='" . $enrolemnt_link . "&secretKey=" . $secretKey . "'/>" . "Please click here" . "</a>";
		$message = "Dear " . $name_meta . ", <br> <br> You have been enrolled by " . $order_data['billing']['company'] . " in the following course " . $course_name . $link . "<br> <br> For full course details and entry requirements please visit <a href='https://amstraining.com.au'>amstraining.com.au </a> <br> your faithfully, <br><br> Ian Cole,<br> AMS TRAINING";

		// Send email to each student
		wp_mail($email_address, 'AMS Enrolment Form Student to complete', $message, $headers);
	}
	$order = wc_get_order($order_id);

	foreach ($order->get_items() as $item_id => $item) {
		$enrolemnt_link = 'https://amstraining.com.au/enrolments/?' . $item->get_meta('enrolemnt_link');
		$course_name = $item->get_meta('course_name');
	}
	$flatString = $list_students;
	$first_name = $order->get_billing_first_name();
	$message = "Dear " . $first_name . ",<br>" . "This is to confirm, the following list of students have been enrolled in the course." . "<br>";
	$message .= "Course Name:" . $course_name . "<br> List of Students <br>" . $flatString;

	// Send confirmation email to the customer
	wp_mail($customer_email, 'AMS Training Registered Students', $message, $headers);
}

session_start();
add_action('woocommerce_before_calculate_totals', 'rudr_custom_price_refresh');

function rudr_custom_price_refresh($cart_object)
{

	foreach ($cart_object->get_cart() as $item) {

		if (array_key_exists('misha_custom_price', $item)) {
			$item['data']->set_price($item['misha_custom_price']);
			$item['data']->set_name($item['course_title']);
			$item['data']->set_stock_quantity($item['stock_quantity']);
		}
	}
}

function send_enrolment_confirmation($order_id)
{
	$order = wc_get_order($order_id);

	if ($order) {
		// Get the billing first name
		$billing_first_name = $order->get_billing_first_name();
		$customer_email = $order->get_billing_email();
	}
	$number_students = $order->get_meta('number_students');
	$course_name =  $order->get_meta('course_name');
	$list = "";
	for ($i = 0; $i <= $number_students - 1; $i++) {
		$name_meta =  '_student_name' . $i;
		$name_meta = $order->get_meta($name_meta);
		$lastname_meta = '_student_last_name' . $i;
		$last_name = $order->get_meta($lastname_meta);
		$email_meta = '_student_email' . $i;
		$email = $order->get_meta($email_meta);
		$list = $list . $name_meta . " " . $last_name . " " . $email . "<br>";
	}
	$message = "Dear " . $billing_first_name . ",<br>" . "This is to confirm, the following list of students have been registered in the course." . "<br>";
	$message = $message . "Course Name:" . $course_name . "<br> List of Students <br>";

	$message = $message . $list;
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Cc: sales@developer.amstraining.com.au' // CC recipient's email
	);
	//wp_mail($customer_email, 'Training Company ' ,  $message ,$headers,'This is for header');
}
add_action('wp_enqueue_scripts', ajax_enqueuescripts);
function my_action_callback7()
{
	//echo "Df";print_r($_POST);die;

	function get_last_order_id()
	{
		global $wpdb;
		$statuses = array_keys(wc_get_order_statuses());
		$statuses = implode("','", $statuses);

		// Getting last Order ID (max value)
		$results = $wpdb->get_col("
        SELECT MAX(ID) FROM {$wpdb->prefix}posts
        WHERE post_type LIKE 'shop_order'
        AND post_status IN ('$statuses')
    ");
		return reset($results);
	}
	$order_id = get_last_order_id();
	$order = wc_get_order($order_id);
	$billing_first_name = $order->get_billing_first_name();
	foreach ($order->get_items() as $item) {
		$product_name = $item['name'];
	}
	$price = $order->get_total();
	$items = $order->get_items();
	$products = array();
	$products[0] = $order_id;
	$products[1] = $price;
	$products[2] =  $product_name;

	$products[3] =  	$billing_first_name;
	$result = implode("?", $products);
	echo  $result;
	die();
}
add_action('wp_ajax_nopriv_ajax_ajaxhandler', 'my_action_callback7');
add_action('wp_ajax_ajax_ajaxhandler', 'my_action_callback7');
add_action('wp_ajax_ajax_ajaxhandler2', 'my_action_callback2');
function refreshArrayCart($discounted_amount_total, $course_code_loop)
{
	session_start();


	//Lets storte the cart 
	global $woocommerce;
	$current_user = wp_get_current_user();
	$saved_student_name = $current_user->student_name;
	$totalProducts = WC()->cart->get_cart();


	//Store the cart in a session
	$listOfpurchasedCourses = array();

	foreach ($totalProducts as $cart_item_key => $cart_item) {
		if ($cart_item['course_code'] == $course_code_loop) {
			$misha_discounted_price = $discounted_amount_total;
		} else {
			$misha_discounted_price = $cart_item['misha_custom_price'];
		}
		$product = array();
		$product['misha_custom_price'] =  $cart_item['misha_custom_price'];
		$product['misha_discounted_price'] =  $misha_discounted_price;
		$product['course_title'] =  $cart_item['course_title'];
		$product['course_code'] =  $cart_item['course_code'];
		$product['course_schedule'] =  $cart_item['course_schedule'];
		$product['start_date'] =  $cart_item['start_date'];
		$product['end_date'] =  $cart_item['end_date'];
		$product['dates'] =  $cart_item['dates'];
		$product['plan_title'] =  $cart_item['plan_title'];
		$product['enrolemnt_link'] =  $cart_item['enrolemnt_link'];
		$product['quantity'] =  $cart_item['quantity'];
		$product['course_plan'] =  $cart_item['course_plan'];

		array_push($listOfpurchasedCourses, $product);
	}
	$_SESSION["purchasedCourseWooCommerceObjectArray"] = $listOfpurchasedCourses;
}
function getMail($first, $last, $email, $course_code)
{
	//'RIIWHS204E-2'
	//$course_code = "RIIWHS202E-4";
	$check =  get_rtodata(array('first_name' => $first, 'last_name' => $last, 'course_code' => $course_code, 'email' => $email, 'type' => 'enrolment'), 'check');
	return implode("", $check['data']);
	//return $course_code;
}
add_action('wp_ajax_ajax_ajaxhandler2', 'my_action_callback2');
function my_action_callback2()
{
	$_SESSION["error-message"] = "True";
	die();
}
function my_enqueue()
{
	wp_enqueue_script('ajax-script', get_template_directory_uri() . '/assets/js/ajax-script.js', array('jquery'));
	wp_localize_script('ajax-script', 'my_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'my_enqueue');
add_action('wp_ajax_nopriv_more_posts', 'get_more_posts');

function get_more_posts()
{
	// How to get id here to query for the database
	echo "Hello World";
	echo $_GET['id'];
	exit();
}
function getAvailbility($purchaseCourseTitle = '', $course_code, $dates)
{
	$args = array(
		'post_type'      => 'shop_order',
		'post_status'    => array('wc-processing', 'wc-completed'), // Specify the desired order statuses
		'posts_per_page' => 100
	);

	$orders = get_posts($args);
	$item_name = $purchaseCourseTitle;
	$item_count = 0;
	foreach ($orders as $order) {
		$order_id = $order->ID;
		$current_order = wc_get_order($order_id);

		$order_items = $current_order->get_items();

		foreach ($order_items as $item_id => $item_data) {
			$product_name = $item_data->get_name();
			$string =  $product_name;
			$phrases = array("$course_code", "$dates");

			$allPhrasesExist = true;

			foreach ($phrases as $phrase) {
				if (strpos($string, $phrase) === false) {
					$allPhrasesExist = false;
					break;
				}
			}

			if ($allPhrasesExist) {

				$product_id = $item_data->get_product_id();
				$quantity = $item_data->get_quantity();
				$total = $item_data->get_total();
				$item_count = $item_count + $quantity;
				// Do something with the matched order item data





			}
		}
	}
	return  $item_count;
}
function searchInMultiArray($array, $key, $value)
{
	$results = array();

	if (is_array($array)) {
		if (isset($array[$key]) && $array[$key] == $value) {
			$results[] = $array;
		}
		foreach ($array as $subarray) {
			$results = array_merge($results, searchInMultiArray($subarray, $key, $value));
		}
	}

	return $results;
}


function include_google_review_template()
{
	ob_start();
	include(get_template_directory() . '/template-parts/chips.php');
	return ob_get_clean();
}
add_shortcode('google_review', 'include_google_review_template');

function getCategoriesAndTheirProductsRto()
{
	$apiResult = get_rtodata(array('type' => 'courses'), 'list');
	$categoriesAndProducts = array();
	for ($i = 0; $i < count($apiResult['data']); $i++) {
		$categoriesAndProducts[$i]['category'] = $apiResult['data'][$i]['label'];
		$categoriesAndProducts[$i]['category-slug'] = $apiResult['data'][$i]['category'];
		$categoriesAndProducts[$i]['products'] = $apiResult['data'][$i]['courses'];
	}
	return $categoriesAndProducts;
}

function include_calendar_with_scripts_shortcode()
{
	ob_start(); // Start output buffering
?>

	<script src="https://amstraining.rtodata.com.au/api/widget/widget.js"></script>

	<!-- Swiper JS -->
	<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

	<?php
	include 'template-parts/calendar/calendar.php';
	wp_enqueue_script('calendar', get_stylesheet_directory_uri() . '/template-parts/calendar/calendar.js', false, '1.1', 'all');
	?>

<?php
	return ob_get_clean(); // Get and clean the buffered output
}
add_shortcode('calendar_with_scripts_shortcode', 'include_calendar_with_scripts_shortcode');

//NOW THE JS ARE IN EACH ELEMENT
wp_enqueue_script('Thankyou', get_stylesheet_directory_uri() . '/template-parts/Thankyou/Thankyou.js', false, '1.1', 'all');
//wp_enqueue_script( 'contactForm3', get_stylesheet_directory_uri() . '/template-parts/contactForm3/contactForm3.js',false,'1.1','all');
//wp_enqueue_script( 'chips', get_stylesheet_directory_uri() . '/template-parts/chips/chips.js',false,'1.1','all');
//wp_enqueue_script( 'home3', get_stylesheet_directory_uri() . '/template-parts/home3/home.js',false,'1.1','all');
//wp_enqueue_script( 'calendar', get_stylesheet_directory_uri() . '/template-parts/calendar/calendar.js',false,'1.1','all');
//wp_enqueue_script( 'calendar-shortcuts', get_stylesheet_directory_uri() . '/template-parts/calendar/calendar-shortcuts.js',false,'1.1','all');
wp_enqueue_script('calendar-carousel', get_stylesheet_directory_uri() . '/template-parts/calendar/calendar-carousel.js', false, '1.1', 'all');
//wp_enqueue_script( 'menu4', get_stylesheet_directory_uri() . '/template-parts/menu4/menu4.js',false,'1.1','all');

//wp_enqueue_script( 'checkout', get_stylesheet_directory_uri() . '/checkout.js',false,'1.1','all');
//wp_enqueue_script( 'Menu3', get_stylesheet_directory_uri() . '/template-parts/menu3/menu3.js',false,'1.1','all');

/********************** */
function getScheduleCode($course_code, $course_plan, $date)
{
	$result = get_rtodata(['code' => $course_code, 'plan' => $course_plan], 'detail');
	$test = [];
	$schedule_code = "";
	$date_length = strlen($date);
	foreach ($result['data']['plan']['schedules'] as $course) {
		$course_date = $course['start_date'] . "-" . $course['end_date'];
		if ($date_length == 10) {
			$course_date = $course['start_date'];
		}
		if (($date === $course_date)) {
			return  $schedule_code = $course['id'];
		}
	}
}

if (!function_exists('blank_canvas_setup')) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function blank_canvas_setup()
	{

		// Add support for editor styles.
		add_theme_support('editor-styles');

		// Enqueue editor styles.
		add_editor_style('variables.css');

		// Editor color palette.
		$colors_theme_mod = get_theme_mod('custom_colors_active');
		$primary          = (!empty($colors_theme_mod) && 'default' === $colors_theme_mod || empty(get_theme_mod('seedlet_--global--color-primary'))) ? '#000000' : get_theme_mod('seedlet_--global--color-primary');
		$secondary        = (!empty($colors_theme_mod) && 'default' === $colors_theme_mod || empty(get_theme_mod('seedlet_--global--color-secondary'))) ? '#007cba' : get_theme_mod('seedlet_--global--color-secondary');
		$foreground       = (!empty($colors_theme_mod) && 'default' === $colors_theme_mod || empty(get_theme_mod('seedlet_--global--color-foreground'))) ? '#333333' : get_theme_mod('seedlet_--global--color-foreground');
		$tertiary         = (!empty($colors_theme_mod) && 'default' === $colors_theme_mod || empty(get_theme_mod('seedlet_--global--color-tertiary'))) ? '#FAFAFA' : get_theme_mod('seedlet_--global--color-tertiary');
		$background       = (!empty($colors_theme_mod) && 'default' === $colors_theme_mod || empty(get_theme_mod('seedlet_--global--color-background'))) ? '#FFFFFF' : get_theme_mod('seedlet_--global--color-background');

		add_theme_support(
			'editor-color-palette',
			array(
				array(
					'name'  => __('Primary', 'blank-canvas'),
					'slug'  => 'primary',
					'color' => $primary,
				),
				array(
					'name'  => __('Secondary', 'blank-canvas'),
					'slug'  => 'secondary',
					'color' => $secondary,
				),
				array(
					'name'  => __('Foreground', 'blank-canvas'),
					'slug'  => 'foreground',
					'color' => $foreground,
				),
				array(
					'name'  => __('Tertiary', 'blank-canvas'),
					'slug'  => 'tertiary',
					'color' => $tertiary,
				),
				array(
					'name'  => __('Background', 'blank-canvas'),
					'slug'  => 'background',
					'color' => $background,
				),
			)
		);
	}
endif;
add_action('after_setup_theme', 'blank_canvas_setup', 11);

function getCourseCategory($code, $plan)
{
	return  get_rtodata(array('code' => $code, 'plan' => $plan), 'detail');
}

/**   Creating payment gateway */


/**Cart list */
add_action('woocommerce_before_checkout_form', 'bbloomer_cart_on_checkout_page', 11);

function bbloomer_cart_on_checkout_page()
{
	echo do_shortcode('[woocommerce_cart]');
}
add_action('woocommerce_checkout_update_order_review', 'bbloomer_checkout_radio_choice_set_session');

function bbloomer_checkout_radio_choice_set_session($posted_data)
{
	parse_str($posted_data, $output);
	if (isset($output['radio_choice'])) {
		WC()->session->set('radio_chosen', $output['radio_choice']);
	}
}


add_filter('woocommerce_enable_order_notes_field', '__return_false');

function my_custom_code_in_footer()
{ ?>



	<script>
		function myFunctionremove(first, last, email, date) {
			// alert(email);
			//  document.getElementById("Student_search_results").style="display:none!important;";
			sessionStorage.setItem("DeleteStudent", "True");
			const storedArrayString = sessionStorage.getItem('listPreviousStudent');
			var phraseToRemove = email;

			var newStr = storedArrayString.replace(phraseToRemove, "");
			sessionStorage.setItem('listPreviousStudent', newStr);
			// Get a reference to the checkbox element
			const checkbox = document.getElementById('delete_button');

			// Check the checkbox using JavaScript
			checkbox.checked = true;

			document.getElementById("preloader").style = "display:block!important;";
			if (document.getElementById("preloaderText")) {
				document.getElementById("preloaderText").textContent = "Deleting ".concat(first).concat(" ").concat(last).concat(" ").concat(email);
			}
			var elements = document.querySelectorAll('span.error-message');

			// Loop through each matched element
			for (var i = 0; i < elements.length; i++) {
				var element = elements[i];
				// Perform actions on each element
				// For example, you can change the text content:
				element.textContent = 'New text';
			}

			jQuery.ajax({
				type: 'POST',
				url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
				data: {
					action: 'ajaxhandler90',
					type: "removestudent",
					removestudent: email,
				},
				success: function(data) {
					// var data = jQuery.parseJSON(data);
					// alert('2229');
					//	 alert(JSON.stringify(data));

					let type = "delete";
					create_students(data, type, date);
					// alert('2232');
					document.getElementById("preloader").style = "display:none!important;";
					// document.getElementById("Student_search_results").style="display:block!important;";
					var element = document.getElementById("Student_search_results");
					var numberOfChildren = element.getElementsByTagName('*').length;

					if (numberOfChildren === 0) {
						let previousEmail = [];
						document.getElementById("woocomerce-quantity").style = "display:none;";
						sessionStorage.setItem('PreviousEmail', JSON.stringify(previousEmail));
						jQuery.ajax({
							type: 'POST',
							url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
							data: {
								action: 'ajaxhandler90',
								type: "clearout",
							},
							success: function(data) {

							}

						});

					}


				}

			});
		}
		jQuery(function($) {
			let timeout;

			let quantityInputs = $('input.qty');
			quantityInputs.each(function() {
				// Perform actions on each quantity input field
				// For example, you can access the value or add event listeners

				$(this).val(1);
				//alert(typeof clickNumber );

			});

			//document.getElementById("available_position").value = "";
			function setQuantityStudents(action, clickNumber) {
				//  alert(action);
				let quantityInputs = $('input.qty');
				let quantity;
				let DeleteStudent = sessionStorage.getItem("DeleteStudent");
				//alert(DeleteStudent);
				quantityInputs.each(function() {
					// Perform actions on each quantity input field
					// For example, you can access the value or add event listeners
					var previous_quantity = parseInt($(this).val());
					quantity = parseInt($(this).val());
					//alert(typeof clickNumber );
					if (clickNumber) {

						if (clickNumber !== 1) {
							if (DeleteStudent !== "True") {
								quantity = quantity + 1;
								$(this).val(quantity);
							}
							if (DeleteStudent === "True") {
								quantity = quantity - 1;
								$(this).val(quantity);
							}

						}

					}
					if (action === "decrease") {
						// alert(action);
						//$(this).val(0);
						//alert('rto');
						if (previous_quantity !== 1) {
							quantity = quantity - 1;
							//alert(quantity);
							$(this).val(quantity);
							// $('#get_price').text((numericValues*quantity));

						}
					}
				});

				return quantity;
			}

			function setCurrentSubTotal(quantity) {
				var re = /(\d+\.\d+)/.exec($(".woocommerce-Price-amount").html());
				let subTotal = parseInt(re[0] * quantity);
				$('#get_price').text(("$".concat(subTotal)));


			}

			function decrease_student_quantity() {
				let currentQuantity = setQuantityStudents("decrease");
				//alert(currentQuantity);
				var re = /(\d+\.\d+)/.exec($(".woocommerce-Price-amount").html());
				setCurrentSubTotal(currentQuantity);
			}

			function validateEmail(email) {
				let str = email;
				let newStr = email.replace(/\s/g, "");
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailRegex.test(newStr);
			}

			function setQuantityStudents(action, clickNumber) {
				//  alert(action);
				let quantityInputs = $('input.qty');
				let quantity;
				let DeleteStudent = sessionStorage.getItem("DeleteStudent");
				// Get a reference to the checkbox element
				const checkbox = document.getElementById('delete_button');

				// Get a reference to the paragraph element where we'll display the result
				const result = document.getElementById('result');

				// Check if the checkbox is checked
				if (checkbox.checked) {
					DeleteStudent = "True";
				} else {
					DeleteStudent = "False";
				}

				quantityInputs.each(function() {
					// Perform actions on each quantity input field
					// For example, you can access the value or add event listeners
					var previous_quantity = parseInt($(this).val());
					quantity = parseInt($(this).val());
					//alert(typeof clickNumber );
					if (clickNumber) {
						//alert('rto');
						if (clickNumber !== 1) {
							if (DeleteStudent !== "True") {
								quantity = quantity + 1;
								$(this).val(quantity);
							}
							if (DeleteStudent === "True") {
								quantity = quantity - 1;
								if (quantity == 0) {
									quantity = quantity + 1;
								}
								$(this).val(quantity);

							}
						}

					}
					if (action === "decrease") {
						// alert(action);
						//$(this).val(0);
						if (previous_quantity !== 1) {
							quantity = quantity - 1;
							//alert(quantity);
							$(this).val(quantity);
							// $('#get_price').text((numericValues*quantity));

						}



					}
				});
				sessionStorage.setItem("DeleteStudent", "Null");
				return quantity;
			}

			function getCourseType() {
				let available_position = (getAvailablePosition()).element_exist;
				let course_type = "Face";
				if (available_position) {
					course_type = "Face";
				} else {
					course_type = "Online";
				}
				return course_type;
			}

			function getAvailablePosition() {
				let enough_position = false;
				let position_data = {};
				let available_position = document.getElementById("available_position");
				if (available_position) {
					available_position = document.getElementById("available_position").value;
					if (available_position > 0) {
						enough_position = true;
						position_data.element_exist = true;
					}

				} else {
					position_data.element_exist = false;
				}
				position_data.enough_position = enough_position;
				return position_data;
			}

			function check_already_enrolled(student_name, last_name, email, start_date) {
				// alert(student_name);
				return new Promise(function(resolve, reject) {
					$.ajax({
						url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
						type: 'POST',
						data: {
							action: 'my_ajax_enrol_action',
							last_name: last_name,
							student_name: student_name,
							email: email,
							start_date: start_date
							// additional data to send if needed

						},
						success: function(response) {
							resolve(response)

						},
						error: function(error) {
							// Handle the error
							alert('889');
							//alert(error);
						}
					});

				});

			}

			function set_error_message(span_element, name, input) {

				const divElement = document.getElementById('Student_search_results');
				//alert(divElement);
				// Check the number of children inside the div
				const numberOfChildren = divElement.childElementCount;
				if (numberOfChildren < 1) {

					document.getElementById('student-list').style = "display:none!important";
				}











				if (!span_element) {
					var span = document.createElement("span");
					// Set the text content of the span element
					span.textContent = name;
					span.style = "color:red!important;";
					span.className = "error-message";
					span.id = "error-message-email-empty";
					// Append the span element after the input
					input.parentNode.insertBefore(span, input.nextSibling);

					input.style = "border-color:red!important;"

				} else {
					span_element.textContent = name;
					span_element.style = "color:red!important;";
					span_element.className = "error-message";
					// span_element.id = "error-message-email";
					// Append the span element after the input
					input.parentNode.insertBefore(span, input.nextSibling);

				}


			}

			function check_validation(email, student_name, last_name, clickNum, type) {
				//alert('2626');
				let valid = false;
				let edit = false;
				email = email.toLowerCase();
				// console.log(email);
				if (type === "Edit") {
					let valid_email = validateEmail(email);
					if (email !== "" && student_name !== "" & last_name !== "" && valid_email === true) {
						valid = true;
					}
					return valid;
				}
				//alert('2593');

				if ((document.getElementById("add_next_student").innerHTML === "Edit")) {
					edit = true;
				}
				//alert("2605".concat(edit));
				let available_position_element = (getAvailablePosition()).element_exist;
				let available_position = (getAvailablePosition()).enough_position;
				if (edit === true) {
					available_position = true;
				}

				function check_available_position() {
					let valid_pos = false;
					//alert("2837".concat(available_position));
					if (!available_position) {
						//	$('#available_position_field').html('<div class="error">An error occurred. Please try again.</div>');
						// Get the input element
						var input = document.getElementById("available_position");

						// Create a new span element
						if (input) {
							if (!(document.getElementById("error-message-position-available"))) {
								var span = document.createElement("span");

								// Set the text content of the span element
								span.textContent = "This course date is now full.Please select a new course date for future students";
								span.style = "color:red!important;";
								span.className = "error-message";
								span.id = "error-message-position-available";
								// Append the span element after the input
								input.parentNode.insertBefore(span, input.nextSibling);
								document.getElementById("available_position").style = "border-color:red!important;";

							} else {
								var span = document.getElementById("error-message-position-available");

								// Set the text content of the span element
								span.textContent = "This course date is now full.Please select a new course date for future students";
								span.style = "color:red!important;";
								span.className = "error-message";
								span.id = "error-message-position-available";
								// Append the span element after the input
								input.parentNode.insertBefore(span, input.nextSibling);
								document.getElementById("available_position").style = "border-color:red!important;";

							}


						}


					}
					if (available_position && available_position_element) {
						valid_pos = true;
					}
					//alert("2880".concat(available_position));
					//alert("2881".concat(valid_pos));
					return valid_pos;
				}


				let course_type = getCourseType();
				let check_available_pos = check_available_position();
				var element = document.getElementById('available_dates');
				if (element) {
					if (check_available_pos === false) {
						return valid = check_available_pos;
					}
				} else {
					// Element doesn't exist
					// Your code here
				}


				if (edit === false) {
					check_available_pos = true;
				}
				//alert(check_available_pos);
				// alert(check_available_pos);
				//	let available_position = (getAvailablePosition()).enough_position;
				//  let  available_dates = document.getElementById("available_position")
				if (!(input)) {
					if (email !== "" && student_name !== "" & last_name !== "") {
						valid = true;
						//alert('2898'.concat(valid));
					}
					var element = document.getElementById('available_dates');
					if (element) {
						valid = check_available_pos;
					} else {
						// Element doesn't exist
						// Your code here
					}

				}

				if (course_type === "Face") {
					if (!available_position) {
						valid = false;
					}
				}
				const emailAddress = email;
				const isValidEmail = validateEmail(emailAddress);
				// let check_duplicate_student = false;

				if (isValidEmail) {} else {
					valid = false;
					document.getElementById("student_email_adress").style = "border-color:red!important;";
					var span_element = document.getElementById("error-message-email-empty");
					let name = "Please use a valid email address";
					var input = document.getElementById("student_email_adress");
					set_error_message(span_element, name, input);
					document.getElementById("preloader").style = "display:none!important;";
				}


				if (valid === false) {
					if (email == "") {
						var span_element = document.getElementById("error-message-email-empty");
						let name = "The email address field cannot be empty. Please provide a valid email address";
						var input = document.getElementById("student_email_adress");
						set_error_message(span_element, name, input);

					}
					if (student_name == "") {

						var span_element = document.getElementById("error-message-last-name-empty");
						let name = "The first name field cannot be empty. Please provide a valid first name";
						var input = document.getElementById("student_name");
						// set_error_message(span_element,name,input);

						document.getElementById("student_name").style = "border-color:red!important;";
					}
					if (last_name == "") {


						var span_element = document.getElementById("error-message-first-name-empty");
						let name = "The last name field cannot be empty. Please provide a valid first name";
						var input = document.getElementById("student_name_last_name");
						//	 set_error_message(span_element,name,input);

						document.getElementById("student_name_last_name").style = "border-color:red!important;";
					}


				}
				var input = document.getElementById("available_position");
				if (input) {
					if (check_available_pos === false) {
						valid = false;
					}
				}
				//alert("check available pos".concat(valid));
				// alert(check_already_enrolled(student_name,last_name));
				//  alert('2959'.concat(valid));
				return valid;
			}

			function add_new_student_ajax(email, student_name, last_name, type) {
				var date = new Date($('#date_of_birth').val());
				var day = date.getDate();
				var month = date.getMonth() + 1;
				var year = date.getFullYear();
				var dob = ([day, month, year].join('-'));

				const element = document.getElementById('available_dates');
				let splitResult = "";
				if (element) {
					let date = document.getElementById("available_dates").value;
					const email2 = date;
					splitResult = email2.split("@")[0];
				} else {
					splitResult = "Empty";
				}
				jQuery.ajax({
					type: 'POST',
					url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
					data: {
						action: 'ajaxhandler9',
						email: email,
						student_name: student_name,
						last_name: last_name,
						available_date: splitResult,
						dob: dob,
						type: 'add',

						nonce: '<?php echo $nonce; ?>'
					},
					success: function(data) {
						//  alert(jQuery.stringify(data));
						create_students(data, type);
						var data = jQuery.parseJSON(data);

						//alert("2700".concat(JSON.stringify(data)));
						// create_students(data);
						/*  jQuery.ajax({
        type: 'POST',
        url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
        data: {
            action: 'ajaxhandler90',
            type: "create_students",
            create_students : true,
            nonce: '<?php echo $nonce; ?>'
        },
        success: function(data) {
           //  alert("2712".concat(JSON.stringify(data)));
			//create_students(data);
			//billing_phone
			var linkElement = document.getElementById("add_next_student");
           var edit =  linkElement.textContent;
            if(edit === "Edit"){
				 var linkElement = document.getElementById("add_next_student");
                 linkElement.textContent = "Add next student(s)";
				document.getElementById("available_dates_field").style="display:block!important";
				document.getElementById("available_position").style="display:block!important";
                
			}
            
			
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert("Fd");
        }
    });*/

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						alert("Fd");
					}
				});
			}

			function setCurrentSubTotal(quantity) {
				var re = /(\d+\.\d+)/.exec($(".woocommerce-Price-amount").html());
				let subTotal = parseInt(re[0] * quantity);
				$('#get_price').text(("$".concat(subTotal)));


			}

			function add_new_student(clickNum) {
				//alert('2671');
				let email = document.getElementById("student_email_adress").value;
				let student_name = document.getElementById("student_name").value;
				let last_name = document.getElementById("student_name_last_name").value;
				//alert(check_validation(email,student_name,last_name));
				if (document.getElementById("add_next_student")) {

				}
				const element = document.getElementById('add_next_student');
				if (element) {
					document.getElementById("add_next_student").style = " pointer-events: none;opacity: 0.7;";
				} else {
					// Element doesn't exist
				}
				// alert('2781');
				//alert(clickNum);
				//alert(check_validation(email,student_name,last_name,clickNum));
				let type = "";
				// Check if an element with ID "myElement" exists
				const element2 = document.getElementById("Edit");
				if (element2) {
					// Element exists
					type = "Edit";
				} else {
					// Element doesn't exist
				}
				//alert('3074'.concat(check_validation(email,student_name,last_name,clickNum,type)));
				if (check_validation(email, student_name, last_name, clickNum, type)) {
					//alert('2812');


					var linkElement = document.getElementById("add_next_student");
					add_new_student_ajax(email, student_name, last_name, type);
					const element_edit = document.getElementById("Edit");
					if (element_edit !== null) {
						// Element exists
					} else {
						// Element does not exist
						let currentQuantity = setQuantityStudents("add", clickNum);
						setCurrentSubTotal(currentQuantity);
					}
				} else if (!(check_validation(email, student_name, last_name, clickNum))) {

					document.getElementById("add_next_student").style = " pointer-events: auto;opacity: 1;";

					document.getElementById("preloader").style = "display:none!important";
				}


				//let edit = document.getElementById("Edit").innerHTML;
				// alert(edit);



			}
			//Edit Functionality
			$(document).on('click', '#Edit', function() {
				var errorMessages = document.querySelectorAll("span.error-message");
				document.getElementById("student-list").style = "display:block!important";
				document.getElementById("woocomerce-cart").style = "display:block!important";

				for (var i = 0; i < errorMessages.length; i++) {
					var errorMessage = errorMessages[i];
					errorMessage.parentNode.removeChild(errorMessage);
				}
				document.getElementById("preloader").style = "display:block!important";
				//document.getElementById("preloader").innerHTML = "Editing";

				var divElement = document.getElementById("student_email_adress");
				divElement.scrollIntoView({
					behavior: "smooth"
				});

				function duplicateEmail(email) {
					let duplicate = false;
					//let edit = document.getElementById("add_next_student").innerHTML;
					//alert(edit);
					var student_name = document.getElementById("student_name").value;
					console.log("empty email");
					if (email === "" && student_name === "") {
						// duplicate = false;
						//return duplicate;
					}
					// Retrieve the array string from the session storage
					const storedArrayString = sessionStorage.getItem('listPreviousStudent');
					//alert(storedArrayString);
					//  alert("2943".concat(storedArrayString));
					// Convert the array string back to an array using JSON.parse
					const storedArray = JSON.parse(storedArrayString);
					for (let i = 0; i < storedArray.length; i++) {
						let previousEmail = storedArray[i];
						if ((previousEmail == email)) {
							duplicate = true;
						}

					}
					//alert('2877');
					//alert(JSON.stringify(storedArray)); // [1, 2, 3, 4, 5]
					return duplicate;

				}
				let email = document.getElementById("student_email_adress").value;

				document.getElementById("preloaderText").textContent = "Editing ".concat(email);
				let check_duplicateEmail = duplicateEmail(email);
				let edit_mail = sessionStorage.getItem('EditPreviousStudent');
				var $this = $(this),
					clickNum = $this.data('clickNum');
				$this.data('clickNum', ++clickNum);
				// 	alert("2888".concat(duplicateEmail(email)));
				//alert('1278'.concat(check_duplicateEmail));
				if (check_duplicateEmail == false) {
					//alert("not duplicat email");
					//add_new_student(clickNum);
					jQuery.ajax({
						type: 'POST',
						url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
						data: {
							action: 'ajaxhandler90',
							type: "removestudent",
							removestudent: edit_mail,
						},
						success: function(data) {
							var data = jQuery.parseJSON(data);
							//alert(JSON.stringify(data));
							let type = "delete";
							// create_students(data,type);        
							add_new_student(clickNum);
						}

					});





				}


			});

			$(document).on('click', '#add_next_student', function() {
				document.getElementById("student-list").style = "display:block!important";
				document.getElementById("woocomerce-cart").style = "display:block!important";
				document.getElementById("woocomerce-checkout").style = "display:block!important";
				const delete_checkbox = document.getElementById('delete_button');
				delete_checkbox.checked = false;
				//alert('1301');
				var errorMessages = document.querySelectorAll("span.error-message");

				for (var i = 0; i < errorMessages.length; i++) {
					var errorMessage = errorMessages[i];
					errorMessage.parentNode.removeChild(errorMessage);
				}

				document.getElementById("student_details").style = "display:block!important";
				document.getElementById("preloader").style = "display:block!important";
				var element = document.getElementById("student_email_adress");
				element.scrollIntoView({
					behavior: "smooth"
				});

				document.getElementById("student_details").innerHTML = "Enrolled Students";
				let first = document.getElementById("student_name").value;
				let last = document.getElementById("student_name_last_name").value;

				function set_preloader_text() {
					// const edit = document.getElementById("Edit");
					const paragraph = document.getElementById('preloaderText');
					let email = document.getElementById("student_email_adress").value;
					var element = document.getElementById("add_next_student");
					var innerHTML = element.innerHTML;
					//alert(innerHTML);
					if (innerHTML === "Add student(s)") {
						paragraph.textContent = 'Adding student '.concat(first).concat(" ").concat(last).concat(" ").concat(email);
					}
				}
				document.getElementById("woocomerce-quantity").style = "display:block";
				var $this = $(this),
					clickNum = $this.data('clickNum');
				let email = document.getElementById("student_email_adress").value;

				set_preloader_text();
				// Change the text content
				//paragraph.textContent = 'Adding students';
				let PreviousEmailCheck = false;


				let DuplicateStudent = false;
				const elements = document.getElementsByClassName('error-message');
				const elementsArray = Array.from(elements);
				elementsArray.forEach(element => {
					// Remove each element from the DOM
					element.remove();
				});
				var input = document.getElementById("student_email_adress");
				input.style = "border-color:#ebe9eb!important;";


				function createStudentSession(array, email) {
					const listPreviousStudent = array;
					listPreviousStudent.push(email);
					const listPreviousStudentString = JSON.stringify(listPreviousStudent);
					sessionStorage.setItem('listPreviousStudent', listPreviousStudentString);


				}

				function duplicateEmail(email) {
					let duplicate = false;
					email = email.trim();
					email = email.toLowerCase();
					let edit = document.getElementById("add_next_student").innerHTML;
					//alert(edit);
					if (email === "") {
						//	 duplicate = false;
						//return duplicate;
					}
					// Retrieve the array string from the session storage
					const storedArrayString = sessionStorage.getItem('listPreviousStudent');
					// alert("2943".concat(storedArrayString));
					// Convert the array string back to an array using JSON.parse
					const storedArray = JSON.parse(storedArrayString);
					for (let i = 0; i < storedArray.length; i++) {
						let previousEmail = (storedArray[i]).toLowerCase();
						console.log('1433');
						console.log(previousEmail);
						console.log(email);
						if ((previousEmail == email) && (edit !== "Edit")) {
							duplicate = true;
						}

					}
					// Use the retrieved array
					//alert(JSON.stringify(storedArray)); // [1, 2, 3, 4, 5]
					return duplicate;

				}
				if (!clickNum) clickNum = 1;
				if (clickNum === 1) {
					//alert('first time clicked');
					createStudentSession([], email);

				} else {
					//alert('2572');
					//alert(duplicateEmail(email));
					DuplicateStudent = duplicateEmail(email);
				}

				//	alert('2676');

				var element = document.getElementById("Student_search_results");
				var numberOfChildren = element.getElementsByTagName('*').length;
				//  alert(numberOfChildren);
				$this.data('clickNum', ++clickNum);
				if (numberOfChildren === 0) {
					clickNum = 0;
					// alert('empty');
					// Get the reference to the element
					const element = document.getElementById('add_next_student');

					// Change the innerHTML of the element
					element.innerHTML = 'Add student(s)';

					// document.getElementById("").innerHtml = 'Add student(s)';
					createStudentSession([], '');
					DuplicateStudent = duplicateEmail(email);
					//alert('1413');
					jQuery.ajax({
						type: 'POST',
						url: "https://amstraining.com.au/wp-admin/admin-ajax.php",
						data: {
							action: 'ajaxhandler9',
							type: 'clearout',
							nonce: '<?php echo $nonce; ?>'
						},
						success: function(data) {
							var data = jQuery.parseJSON(data);
						},
						error: function(XMLHttpRequest, textStatus, errorThrown) {
							alert("Fd");
						}
					});
					//  setCurrentSubTotal(1);  		  

				}

				var element = document.querySelector("#available_dates");
				var start_date;
				if (element) {
					var available_dates = document.getElementById("available_dates").value;
					var str = available_dates;
					var delimiter = "@";

					// Using substring() and indexOf()
					var extractedSubstring = str.substring(0, str.indexOf(delimiter));
					start_date = extractedSubstring.substring(0, 10);
				} else {
					//  console.log("Element does not exist.");
				}


				//alert('1448');
				check_already_enrolled(first, last, email, start_date).then(function(data) {
					// Run this when your request was successful
					// return;
					let check_enrolled = data.toString();
					//	alert(check_enrolled);
					let edit = document.getElementById("add_next_student").innerHTML;
					let name = first.concat(" ").concat(last).concat(" ").concat("has already enrolled in the course. Do you still wish to continue ?");
					var input = document.getElementById("student_email_adress");

					//	check_enrolled 	= null;






					if (check_enrolled !== "null") {

						var checkbox = document.createElement("input");
						checkbox.type = "checkbox";
						checkbox.id = "myCheckbox"; // Set a unique ID for the checkbox



						// Append the checkbox and label to a container in the HTML document
						//   var container = document.getElementById("student_email_adress_field");
						//     container.appendChild(checkbox);
						// container.appendChild(label);





						document.getElementById("preloader").style = "display:none!important";
						document.getElementById("student_details").style = "display:none!important";

						var span_element = document.getElementById("error-message-email");
						// Create a new span element







						if (!span_element) {
							var span = document.createElement("span");
							// Set the text content of the span element
							span.textContent = name;
							span.style = "color:orange!important;";
							span.className = "error-message";
							span.id = "error-message-email";
							// Append the span element after the input
							//input.parentNode.insertBefore(span, input.nextSibling);

							//	document.getElementById("student_email_adress").style="border-color:red!important;"

						} else {
							span_element.textContent = name;
							span_element.style = "color:orange!important;";
							span_element.className = "error-message";
							span_element.id = "error-message-email";
							// Append the span element after the input
							input.parentNode.insertBefore(span, input.nextSibling);






						}
					}
					check_enrolled = "null";

					if (check_enrolled === "null") {
						//document.getElementById("preloader").style = "display:none!important";
						var divElement = document.getElementById("student_email_adress");
						divElement.scrollIntoView({
							behavior: "smooth"
						});
						if (edit === "Edit") {
							// document.getElementById("add_next_student").innerHTML= "Add next student(s)";
						}
						//alert("3035");
						let student_name = document.getElementById("student_name").value;
						let last_name = document.getElementById("student_name_last_name").value;
						let check_validation2 = true;
						var element = document.getElementById('available_dates');
						if (element) {
							check_validation2 = check_validation(email, student_name, last_name, clickNum);
						} else {
							// Element doesn't exist
							// Your code here
						}


						if (DuplicateStudent === true) {
							var span_element = document.getElementById("error-message-first-empty");
							if (!first) {
								let first_message = "Please enter a first name";
								var first_input = document.getElementById("student_name");
								set_error_message(span_element, first_message, first_input);



							}

							if (!last) {

								let last_message = "Please enter a last name";
								var last_input = document.getElementById("student_name_last_name");
								set_error_message(span_element, last_message, last_input);


							}

							if (!email) {


								let email_message = "Please enter a valid email address";
								var email_input = document.getElementById("student_email_adress");
								set_error_message(span_element, email_message, email_input);






							}




							if (first && last && email) {
								let name = first.concat(" ").concat(last).concat(" ").concat("has already been added to the student list. Please enter a different student email address");
								var input = document.getElementById("student_email_adress");
								set_error_message(span_element, name, input);
								document.getElementById("preloader").style = "display:none!important";
								document.getElementById("preloaderText").style = "display:none!important";
							}



						}
						//alert('3388'.concat(check_validation2));
						if ((DuplicateStudent === false && email !== "" && check_validation2 !== false) || edit === "Edit") {
							//	alert('3390');
							//	alert('3091');
							var $this = $(this),
								clickNum = $this.data('clickNum');

							if (!clickNum) clickNum = 1;
							const storedArrayString = sessionStorage.getItem('listPreviousStudent');
							const storedArray = JSON.parse(storedArrayString);
							createStudentSession(storedArray, email);

							var element = document.querySelector("#available_dates");
							var available_dates = '';
							if (element) {
								available_dates = document.getElementById('available_dates').value;
								available_dates = available_dates.split('@')[0];
							}

							$.ajax({
								url: "/wp-admin/admin-ajax.php",
								type: 'POST',
								data: {
									action: 'add_to_cart_ajax',
									available_dates: available_dates,
									click_num: clickNum
								},
								success: function(response) {
									// Handle the success response.
									// alert('Product added to cart successfully!');
								},
								error: function(error) {
									// Handle the error response.
									console.log('Error adding product to cart.');
								}
							});

							var element2 = document.getElementById("Student_search_results");
							numberOfChildren = element2.getElementsByTagName('*').length;
							//  alert(numberOfChildren);
							// $this.data('clickNum', ++clickNum);
							if (numberOfChildren === 0) {
								//clickNum = 0;
							}
							//alert(clickNum);
							add_new_student(clickNum);
							$this.data('clickNum', ++clickNum);
							const available_dates_field = document.getElementById("available_dates_field");
							if (available_dates_field !== null) {
								document.getElementById("available_dates_field").style = "display:block!important;";
							}

						}



					}
					//alert(edit);



				}).catch(function(err) {
					// Run this when promise was rejected via reject()
					console.log(err)
				});


			});
			$(document).on('click', '#button1', function() {

				decrease_student_quantity();


			});







		});
	</script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$.ajax({
				url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX URL
				type: 'POST',
				data: {
					action: 'custom_user_check' // Updated action name
				},
				success: function(response) {
					var data = JSON.parse(response);
					if (data.logged_in) {
						// User is logged in
						//  alert('User is logged in');
					} else {
						// User is not logged in
						// window.location.href = 'https://amstraining.com.au/';

						document.getElementById("payment_method_pin_payments").click();
					}
				},
				error: function(xhr, status, error) {
					console.error(xhr.responseText);
				}
			});
		});
	</script>
<?
}
add_action('wp_footer', 'my_custom_code_in_footer');
add_action('wp_head', 'your_function');
function your_function()
{ ?>
	<script>
		function create_students(students, type, date) {
			// alert('xyz');
			//alert('line 1697');
			//alert(type);
			console.log('1694');
			// alert(jQuery.parseJSON(students) );
			var character = '@';

			function getSecondPart(selected_date) {
				return selected_date.split('@')[1];
			}

			function getThirdPart(selected_date) {
				return selected_date.split('@')[0];
			}

			function setAvailablePosition(type, selectedOption) {
				var edit = document.getElementById("add_next_student").innerHTML;
				//alert("1710".concat(edit));
				//alert(type);
				if (type !== "delete" && edit !== "Edit") {
					if (getSecondPart(selectedOption.value) !== 0) {
						var quantity = (getSecondPart(selectedOption.value)) - 1;
						if (!(quantity < 0)) {
							var date = getThirdPart(selectedOption.value);
							selectedOption.value = date.concat('@').concat(quantity);
							document.getElementById("available_position").value = quantity;
						}

					}

				}

				if (type === "delete") {
					//alert(type);
					var quantity = parseInt((getSecondPart(selectedOption.value)), 10) + 1;
					var date = getThirdPart(selectedOption.value);
					selectedOption.value = date.concat('@').concat(quantity);
					document.getElementById("available_position").value = quantity;
				}

			}
			var data = jQuery.parseJSON(students);

			//alert('line 1618');
			document.getElementById("preloader").style = "display:none!important";
			//document.getElementById("add_next_student").style =" pointer-events: auto;";
			const myNode = document.getElementById("Student_search_results");
			myNode.innerHTML = '';
			//   alert(JSON.stringify(data));

			//alert('line 1624');
			const element = document.getElementById('available_dates');
			if (element) {
				// The element exists
				// alert('exist');
				var available_date = document.getElementById("available_dates").value;
			} else {
				// The element does not exist
				//alert('not exist');
				var available_date = false;
			}
			let gridContainer = "";
			let gridItem = "";
			if (available_date) {
				/** const email = available_date;
			const splitResult = email.split("@")[0];
			 let date_string = "Student list "+splitResult;
            let student_details = document.getElementById("student_details").innerHTML;
			if(student_details == ""){
				document.getElementById("student_details").innerHTML = date_string; 
			}
			  student_details = document.getElementById("student_details").innerHTML;
			//Comparing if they belong to the same date
			if(student_details !==  date_string){
				//alert('test');
			}**/
				gridContainer = "grid-container";
				gridItem = "grid-item";

			} else {
				gridContainer = "grid-container2";
				gridItem = "grid-item2";

			}

			//alert("2028".concat(JSON.stringify(data)));
			document.getElementById("Student_search_results").innerHTML = "";
			//alert(data);
			//   alert('2031'.concat(data.length));
			for (let i = 0; i < data.length; i++) {
				let parentdiv = document.createElement("div");
				parentdiv.className = gridContainer;
				// alert(JSON.stringify(data));
				let date = data[i].date;

				// const select_date = document.getElementById("available_dates").value;

				let grid_items = document.createElement("div");
				grid_items.className = "grid-item";
				let para = document.createElement("a");
				para.setAttribute("onclick", `myFunction9('${data[i].name}','${data[i].lastname}','${data[i].email}')`);
				let node = document.createTextNode(data[i].email);


				para.appendChild(node);
				grid_items.appendChild(para);



				let parentdiv1 = document.createElement("div");
				parentdiv1.className = "grid-container";

				let grid_items1 = document.createElement("div");
				grid_items1.className = "grid-item";
				let para1 = document.createElement("a");
				para1.setAttribute("onclick", `myFunction9('${data[i].name}','${data[i].lastname}','${data[i].email}')`);
				let node1 = document.createTextNode(data[i].name);
				para1.appendChild(node1);
				grid_items1.appendChild(para1);


				let parentdiv2 = document.createElement("div");
				parentdiv2.className = "grid-container";

				let grid_items2 = document.createElement("div");
				grid_items2.className = "grid-item";
				let para2 = document.createElement("a");
				para2.setAttribute("onclick", `myFunction9('${data[i].name}','${data[i].lastname}','${data[i].email}')`);
				let node2 = document.createTextNode(data[i].lastname);
				para2.appendChild(node2);
				grid_items2.appendChild(para2);


				let grid_items3 = document.createElement("div");
				grid_items3.className = "grid-item";
				var button = document.createElement("a");
				button.setAttribute("onclick", `myFunction9('${data[i].name}','${data[i].lastname}','${data[i].email}')`);
				button.innerHTML = "Edit";


				var button1 = document.createElement("a");
				button1.setAttribute("onclick", `myFunctionremove('${data[i].name}','${data[i].lastname}','${data[i].email}',)`);
				button1.setAttribute("id", "button1");
				button1.innerHTML = "Delete";

				grid_items3.appendChild(button);

				grid_items3.appendChild(button1);





				// parentdiv.append(grid_items1,grid_items2,grid_items,grid_items3);

				const element = document.getElementById("Student_search_results");
				element.classList = [];
				if (available_date) {
					element.classList.add("grid-container2");
					let grid_items4 = document.createElement("div");
					grid_items4.className = "grid-item";
					let para4 = document.createElement("a");
					para4.setAttribute("onclick", `myFunction9('${data[i].name}','${data[i].lastname}','${data[i].email}','${data[i].date}')`);
					//data[i].date
					let node4 = document.createTextNode(data[i].date);
					para4.appendChild(node4);
					grid_items4.appendChild(para4);
					element.append(grid_items1, grid_items2, grid_items, grid_items4, grid_items3);
				} else {
					element.classList.add("grid-container-students-no-date");
					element.append(grid_items1, grid_items2, grid_items, grid_items3);
				}


			}

			//alert(date_string);
			const add_next_student = document.getElementById("add_next_student");
			if (add_next_student) {

				document.getElementById("add_next_student").style = " pointer-events: auto;opacity: 1;";

				var linkElement = document.getElementById("add_next_student");
				var edit = linkElement.textContent;
			}

			// alert("1870".concat(edit));
			//alert("1800");
			if (document.getElementById("Edit")) {
				var edit = document.getElementById("Edit").innerHTML;
				// alert("1882");

				if (edit === "Edit") {
					//	alert("1901");
					document.getElementById("Edit").innerHTML = "Add next student(s)";
					document.getElementById("Edit").id = "add_next_student";
					const available_position_field = document.getElementById("available_position_field");
					if (available_position_field) {
						document.getElementById("available_position_field").style = "display:block!important";
						document.getElementById("available_position").style = "display:block!important";
						document.getElementById("available_dates_field").style = "display:block!important";

					} else {
						// Element doesn't exist
					}

				}
			}

			var selectElement = document.getElementById("available_dates");
			if (selectElement && edit !== "Edit") {
				//  alert(selectElement);
				// alert('1902');
				var selectedOption = selectElement.options[selectElement.selectedIndex];


				var check_quantity = (getSecondPart(selectedOption.value));
				setAvailablePosition(type, selectedOption);




			}


			//	document.getElementById("student_details").style ="Student list for certain date 26/05/2023";

			document.getElementById("student_name").value = "";
			document.getElementById("student_name_last_name").value = "";
			document.getElementById("student_email_adress").value = "";
			if (document.getElementById("Edit")) {
				var edit = document.getElementById("Edit").innerHTML;
				//    alert("1903");

				if (edit === "Edit") {
					//alert("1901");
					document.getElementById("Edit").innerHTML = "Add next student(s)";
					document.getElementById("Edit").id = "add_next_student";
					const available_position_field = document.getElementById("available_position_field");
					if (available_position_field) {
						document.getElementById("available_position_field").style = "display:block!important";
						document.getElementById("available_position").style = "display:block!important";
					} else {
						// Element doesn't exist
					}

				}
			}

		}
		//Edit Function
		function myFunction9(name, lastname, email) {
			const storedArrayString = sessionStorage.getItem('listPreviousStudent');
			var phraseToRemove = email;

			var newStr = storedArrayString.replace(phraseToRemove, "");
			sessionStorage.setItem('listPreviousStudent', newStr);
			sessionStorage.setItem('EditPreviousStudent', email);

			var divElement = document.getElementById("billing_phone");
			divElement.scrollIntoView({
				behavior: "smooth"
			});
			document.getElementById("student_name").value = name;
			document.getElementById("student_name_last_name").value = lastname;
			document.getElementById("student_email_adress").value = email;
			var linkElement = document.getElementById("add_next_student");
			linkElement.textContent = "Edit";
			linkElement.id = "Edit";
			var element = document.getElementById("available_dates");
			if (element !== null) {
				// Element exists
				document.getElementById("available_dates_field").style = "display:none !important;";
				document.getElementById("available_position_field").style = "display:none !important";
			} else {
				// Element does not exist



			}



			//alert(lastname);
			//alert(email);
		}
	</script>

	<script>
		console.log("ready!");
		var email = "aritramitra918@gmail.com";
		var test = "";
		jQuery.ajax({
			type: 'POST',
			url: 'https://amstraining.com.au/wp-admin/admin-ajax.php',
			data: {
				action: 'ajax_ajaxhandler',
				email: email
			},
			success: function(data) {

				//alert(data);
				const myArray = data.split("?");
				const price = myArray[1];
				//alert(price);
				//const myArray = data;
				const product_name = myArray[2];
				//  alert(myArray[1]);
				//    const price = price.toFixed(2);
				// alert(sessionStorage.getItem("order"));
				//alert(myArray[0]);
				if (sessionStorage.getItem("order") !== myArray[0]) {

					window.dataLayer = window.dataLayer || [];
					dataLayer.push({
						ecommerce: null
					});
					dataLayer.push({
						'event': 'purchase',
						'ecommerce': {
							//'purchase': {
							'currency': "AUD",
							'value': price,
							'transaction_id': myArray[0],
							'items': [{
									'name': product_name.concat(myArray[3]), // Name or ID is required.
									'id': myArray[0],
									'price': price,
									'quantity': '1',
									'transaction_id': myArray[0]



								}


							]

						}
					});
					window.uetq = window.uetq || [];
					window.uetq.push('event', 'PRODUCT_PURCHASE', {
						"ecomm_prodid": myArray[0],
						"ecomm_pagetype": "PURCHASE",
						"revenue_value": price,
						"currency": "AUD"
					});

					// alert(price);
					sessionStorage.setItem("order", myArray[0]);
					gtag('event', 'conversion', {
						'send_to': 'AW-10837260091/vYv8CIPh2bMYELvuza8o',
						'value': price,
						'currency': 'AUD',
						'transaction_id': myArray[0],
						'quantity': 1
					});




				}



			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				//   alert("Fd");
			}
		});
	</script>
	<!-- Google Tag Manager -->

	<!-- End Google Tag Manager -->
	<script>
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src =
				'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', 'GTM-TLMZBPJ');
	</script>
	<!-- End Google Tag Manager -->
	<!-- Facebook Pixel Code -->
	<script>
		! function(f, b, e, v, n, t, s) {
			if (f.fbq) return;
			n = f.fbq = function() {
				n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
			};
			if (!f._fbq) f._fbq = n;
			n.push = n;
			n.loaded = !0;
			n.version = \'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');fbq(\'init\', \'1152333235562390\');fbq(\'track\', \'PageView\');
	</script><noscript><img height=\"1\" width=\"1\" style=\"display:none\"src=\https://www.facebook.com/tr?id=1152333235562390&ev=PageView&noscript=1\ /></noscript><!-- End Facebook Pixel Code -->
	<script type="application/ld+json">
		{
			"@context": http: //schema.org, "@type": "LocalBusiness", "name": " Training Company", "image": https://amstraining.com.au/wp-content/uploads/2022/06/Home-Page-Banner.jpg, "telephone": "(08) 8932 4220", "email": "", "address": { "@type": "PostalAddress", "streetAddress": "Office: 1/22 Beresford Road", "addressLocality": "Yarrawonga", "addressRegion": "NT", "postalCode": "0830" }, "url": "https:amstraining.com.au" }
	</script>
	<!-- Facebook Pixel Code -->
<? }
add_action('wp_ajax_ajaxhandler9', 'my_action_callback9');
add_action('wp_ajax_nopriv_ajaxhandler9', 'my_action_callback9');

function my_action_callback9()
{
	if (isset($_POST['type'])) {

		if ($_POST['type'] === "check_enrol") {
			echo 'AJAX request successful';
			wp_die();
		}
		if ($_POST['type'] === "clearout") {
			//session_start();
			$_SESSION["student"] = [];
			return;
		}
	}

	$students_test = array();
	$provided_email = $_POST['email'];
	$data['email'] = $_POST['email'];
	$data['name'] = $_POST['student_name'];
	$data['lastname'] = $_POST['last_name'];
	$data['date'] = $_POST['available_date'];
	$data['dob'] = $_POST['dob'];
	//$data['available_date']= $_POST['available_date'];
	//echo (json_encode($data));
	if (!empty($_SESSION["student"])) {
		$students_test = $_SESSION["student"];
	}
	if (empty($_SESSION["student"])) {
		array_push($students_test, $data);
	}

	//$data['purchase-order'] = $_POST['igfw_purchase_order_number'];


	if (!empty($_SESSION["student"])) {
		$previous_student = $_SESSION["student"];
		$all_emails = array();
		foreach ($previous_student as $key => $value) {
			if ($value['email'] !== $provided_email) {
				$test_email = $value['email'];
				array_push($students_test, $data);
			}
		}
	}

	$temp = array_unique(array_column($students_test, 'email'));
	$unique_arr = array_intersect_key($students_test, $temp);
	array_unique($students_test, SORT_REGULAR);
	//foreach(){}
	$_SESSION["student"] = $unique_arr;


	echo (json_encode($unique_arr));
	if (isset($_POST['remove_students'])) {
		echo (json_encode($data['email']));
	}
	die();
}
function my_ajax_action_callback()
{
	// Perform your server-side logic here
	global $woocommerce;
	$totalProducts = WC()->cart->get_cart();
	$listOfpurchasedCourses = array();
	foreach ($totalProducts as $cart_item_key => $cart_item) {
		if ($cart_item['course_code'] == $course_code_loop) {
			$misha_discounted_price = $discounted_amount_total;
		} else {
			$misha_discounted_price = $cart_item['misha_custom_price'];
		}
		$product = array();
		$product['misha_custom_price'] =  $cart_item['misha_custom_price'];
		$product['misha_discounted_price'] =  $misha_discounted_price;
		$product['course_title'] =  $cart_item['course_title'];
		$product['course_code'] =  $cart_item['course_code'];
		$product['course_schedule'] =  $cart_item['course_schedule'];
		$product['start_date'] =  $cart_item['start_date'];
		$product['end_date'] =  $cart_item['end_date'];
		$product['dates'] =  $cart_item['dates'];
		$product['plan_title'] =  $cart_item['plan_title'];
		$product['enrolemnt_link'] =  $cart_item['enrolemnt_link'];
		$product['quantity'] =  $cart_item['quantity'];
		$product['course_plan'] =  $cart_item['course_plan'];

		array_push($listOfpurchasedCourses, $product);
	}
	$_SESSION["purchasedCourseWooCommerceObjectArray"] = $listOfpurchasedCourses;
	$purchasedCourse = $_SESSION["purchasedCourseWooCommerceObjectArray"];
	$course_code = $purchasedCourse[0]['course_code'];
	$find = getMail($_POST['student_name'], $_POST['last_name'], $_POST['email'], $course_code);
	echo json_encode($find);

	// It's important to exit after sending the response
	wp_die();
}

add_action('wp_ajax_my_ajax_enrol_action', 'my_ajax_action_callback');
add_action('wp_ajax_nopriv_my_ajax_enrol_action', 'my_ajax_action_callback');
add_action('wp_ajax_ajaxhandler90', 'my_action_callback90');
add_action('wp_ajax_nopriv_ajaxhandler90', 'my_action_callback90');

function my_action_callback90()
{

	if (isset($_POST['type'])) {
		if (isset($_POST['create_students'])) {
			session_start();
			$email = $_POST['email'];
			$data = $_SESSION["student"];

			echo (json_encode($data));
			die();
		}
	}


	if (isset($_POST['type'])) {
		if (isset($_POST['clearout'])) {

			$_SESSION["student"] = [];
			session_destroy();
			// echo (json_encode( ['x'] ));
			//die();
		}

		if (isset($_POST['removestudent'])) {
			$student = $_SESSION["student"];

			foreach ($student as $key => $value) {
				$found = array();
				$email = $value['email'];
				if ($email === $_POST['removestudent']) {
					unset($student[$key]);
				}
			}
			$student = array_values($student);
			$_SESSION["student"] = $student;

			echo (json_encode($student));
			die();
		}
	}
}
add_action('wp_ajax_add_to_cart_ajax', 'add_to_cart_ajax');
add_action('wp_ajax_nopriv_add_to_cart_ajax', 'add_to_cart_ajax');
function add_to_cart_ajax()
{
	$click_num = intval($_POST['click_num']);
	//return;
	if ($click_num === 1) {
		WC()->cart->empty_cart(true);
		$StorePurchaseCourse = array();
		session_start();
		$_SESSION['StorePurchaseCourse'] = $StorePurchaseCourse;
	}

	$product_id = intval($_POST['product_id']);
	$available_dates =  $_POST['available_dates'];
	$purchasedCourse = $_SESSION["purchasedCourseWooCommerceObjectArray"];
	$course_code = $purchasedCourse[0]['course_code'];
	$course_plan = $purchasedCourse[0]['course_plan'];
	$course_schedule = $purchasedCourse[0]['course_schedule'];
	$course_title =  $purchasedCourse[0]['course_title'];
	$start_date = $purchasedCourse[0]['start_date'];
	$end_date = $purchasedCourse[0]['end_date'];
	$dates = $purchasedCourse[0]['dates'];
	$plan_title = $purchasedCourse[0]['plan_title'];
	$enrolment_link = $purchasedCourse[0]['enrolemnt_link'];
	$misha_custom_price = $purchasedCourse[0]['misha_custom_price'];
	$misha_discounted_price = $purchasedCourse[0]['misha_discounted_price'];
	$end_date = substr($available_dates, 11);
	$start_date = substr($available_dates, 0, 10);
	$dates = $start_date . ' - ' . $end_date;
	$course_code_no_hypehn = explode('-', $course_code)[0];
	$purchaseCourseTitle = $course_code_no_hypehn . ' ' . $plan_title . ' ' . $dates . ' | ';
	$StorePurchaseCourse = $_SESSION['StorePurchaseCourse'];
	array_push($StorePurchaseCourse, $purchaseCourseTitle);

	//$coursTitle = $result['data']['course_title'].' '.explode('-',$result['data']['course_code'])[0];
	WC()->cart->add_to_cart(1074, 1, 0, array(),  array(
		'misha_custom_price' => $misha_custom_price,
		'misha_discounted_price' => $misha_discounted_price,
		'course_title' => $purchaseCourseTitle,
		'course_code' => $course_code,
		'course_plan' => $course_plan,
		'course_schedule' => $course_schedule,
		'start_date' => $start_date,
		'end_date' => $end_date,
		'dates' => $dates,
		'plan_title' => $plan_title,
		'enrolemnt_link' => $enrolment_link,
		'stock_quantity' => 1
	));
	$checkout = WC()->checkout();












	// Add the product to the cart.
	// WC()->cart->add_to_cart($product_id, $quantity);

	// Return a response indicating success.
	wp_send_json_success();
}

function my_ajax_function()
{
	$response_data = array();

	// Retrieve the billing_first_name field from the AJAX request
	$billing_first_name = isset($_POST['billing_first_name']) ? sanitize_text_field($_POST['billing_first_name']) : '';

	// Your AJAX logic here

	// Include the billing_first_name in the response data
	$response_data['billing_first_name'] = $billing_first_name;
	global $wpdb;

	// Define your custom table name
	$table_name = $wpdb->prefix . 'billing_session';
	$students_test = $_SESSION["student"];
	// Define your data to be inserted
	$data = array(
		'Billing_email_address' => $billing_first_name,
		'data' => json_encode($students_test),

	);

	// Insert data into the custom table
	$wpdb->insert($table_name, $data);
	if (is_null($students_test)) {
		$response_data = 'null-student';
	}

	//$response_data = 'null-student';






	// Return the response data
	wp_send_json($response_data);
}
add_action('wp_ajax_my_action_add_student', 'my_ajax_function'); // For logged-in users
add_action('wp_ajax_nopriv_my_action_add_student', 'my_ajax_function'); // For non-logged-in users
add_action('woocommerce_admin_order_item_headers', 'my_woocommerce_admin_order_item_headers');
function my_woocommerce_admin_order_item_headers($order)
{
	// set the column name
	$column_name = 'List of Students';
	$order_meta = get_post_meta($order_id);
	// Get the number of entered students
	$count = get_post_meta($order->ID, 'number_students', 1);
	// Looping over number of students 
	for ($i = 0; $i < $count; $i++) {
		$name = '_student_name' . $i;
		$last_name = '_student_last_name' . $i;
		$email = '_student_email' . $i;
		$available = '_available_dates' . $i;
		$name = get_post_meta($order->ID, $name, 1);
		$lastname = get_post_meta($order->ID, $last_name, 1);
		$email = get_post_meta($order->ID, $email, 1);
		$available = 	get_post_meta($order->ID, $available, 1);
		$message = $message . "" . $name . " " . $lastname . ' ' . $available . '<br> ';
	}


	session_start();

	// Store data in the session
	$_SESSION['message'] = $message;
	echo '<th>' . $column_name . '</th>';
}
// Add a custom action after a successful checkout
add_action('woocommerce_admin_order_item_values', 'my_woocommerce_admin_order_item_values', 10, 3);
function my_woocommerce_admin_order_item_values($_product, $item, $item_id = null)
{

	$message =  $_SESSION['message'];
	if ($item['type'] == "line_item")
		echo '<td>' . $message . '</td>';
}

add_action('wp_ajax_custom_file_upload', 'custom_file_upload');
add_action('wp_ajax_nopriv_custom_file_upload', 'custom_file_upload');
function custom_file_upload()
{
	$uploaded_file = $_FILES['file'];
	$_SESSION['purchase_order'] = $_POST['purchase_order'];
	// Process the uploaded file as needed
	// For example, you can move the file to a specific directory
	$upload_dir = wp_upload_dir();

	if (!empty($upload_dir['basedir'])) {
		$user_dirname = $upload_dir['basedir'] . '/product-images';
		if (!file_exists($user_dirname)) {
			wp_mkdir_p($user_dirname);
		}
		$course_title = (WC()->session->get('cart'))[0]['course_title'];
		$first_name = (WC()->session->get('customer'))["first_name"];
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$filename = wp_unique_filename($user_dirname, $_FILES['file']['name']);
		move_uploaded_file($_FILES['file']['tmp_name'], $user_dirname . '/' . $filename);
		$attachments = array($user_dirname . '/' . $filename);
		// $student = $_SESSION['student'];
		$jsonData = $_POST["studentInfo"];

		$decodedString = stripslashes(json_encode($jsonData));
		$decodedString = stripslashes($decodedString);
		$decodedString = trim($decodedString, '"');
		// Use json_decode to convert the JSON string to a PHP array
		$info = json_decode($decodedString, true);
		$students_test = $info;
		echo $decodedString;
		wp_die();
		$message = "Dear Ian Cole,<br> " . "Attached is a purchase order from " . $first_name . " The purchase order is" . $_POST['purchase_order'] . "The following students have been enrolled" . "<br>" . json_encode($students_test);
		wp_mail('developer@twomoonsconsulting.com.au', 'Purchase order or Invoice number', $message, $headers, $attachments);
		rmdir($user_dirname);
		// unlink($user_dirname .'/'. $filename);
		// $wp_filesystem->delete($user_dirname, true);
		// save into database $upload_dir['baseurl'].'/product-images/'.$filename;
	}

	// Return a response if needed
	echo 'File uploaded successfully';
}

add_action('wp_ajax_standing_agrement_number', 'standing_agrement_number');

// Register AJAX callback for non-authenticated users
add_action('wp_ajax_nopriv_standing_agrement_number', 'standing_agrement_number');

// AJAX callback function
function standing_agrement_number()
{
	$user_id = get_current_user_id();  // Get the ID of the current user

	$biography = get_the_author_meta('description', $user_id);

	if (!empty($biography)) {
		// Biographical info exists
		echo $biography;
	} else {
		// Biographical info does not exist
		echo "Biographical information not available.";
	}



	wp_die();
}
add_action('woocommerce_checkout_create_order', 'custom_checkout_field_update_order_meta', 20, 2);
function custom_checkout_field_update_order_meta($order, $data)
{
	$order_id = $order->get_id();
	//do_action( 'wc_xero_send_payment', $order_id );
	$order_id = $order->get_id();
	//do_action( 'wc_xero_send_payment', $order_id );
	// Assuming you have already set up a custom table in the WordPress database
	$billing_email = $order->get_billing_email();
	global $wpdb;

	// Replace 'custom_table_name' with the actual name of your custom table
	$table_name = $wpdb->prefix . 'billing_session';

	// Replace 'billing_email_value' with the specific email address you want to search for
	//$billing_email = 'billing_email_value';

	// Prepare the SQL query with a placeholder to avoid SQL injection and retrieve the latest record
	$query = $wpdb->prepare(
		"SELECT * FROM $table_name WHERE Billing_email_address = %s ORDER BY timestamp_column DESC LIMIT 1",
		$billing_email
	);

	// Execute the query and get the latest record
	$latest_record = $wpdb->get_row($query);

	// Process the latest record as needed
	if ($latest_record) {
		// Access the data from the $latest_record object and do something with it
		$student = $latest_record->data;
		// Additional data retrieval if necessary
	} else {
		// No records found for the specified email address
	}
	$course_information =  $_SESSION["purchasedCourse"];
	//$schedule = getScheduleCode($course_information['course_code'],$purchasedCourseWooCommerce[0]['course_plan'],$value['date']);

	$link = 'https://amstraining.com.au/enrolments/?code=' . $course_information['course_code'] . '&plan=' . $course_information[0]['course_plan'] . '&schedule=' . '';
	$order->update_meta_data('_students_link', $link);
	$order->update_meta_data('course_name', $course_information[0]['plan_title']);
	$order->save();
	if (isset($_POST['student_name'])) {
		$billing_email = $order->get_billing_email();
		$student =  json_decode($student);
		//$student = $_SESSION["student"];
		$student_count = count($student);
		$count = 0;
		global $woocommerce;
		$purchasedCourse = $_SESSION["purchasedCourse"];

		$purchasedCourseWooCommerce =  $_SESSION["purchasedCourseWooCommerceObjectArray"];

		$_SESSION["purchasedCourseWooCommerceObjectArrayBACKUP"] = $_SESSION["purchasedCourseWooCommerceObjectArray"];
		$items = $woocommerce->cart->get_cart();
		$discounted_amount_total = 0;
		foreach ($items as $item) {
			$course_name = $item['course_title'];
			$course_code_loop = $item['course_code'];
		}


		$_SESSION["finalPurchase"] = $_SESSION["purchasedCourseWooCommerceObjectArray"][0];
		$_SESSION["finalPurchase"]['data_purchased'] = date("m/d/Y h:m a");


		$student_array = [];
		foreach ($student as $key => $value) {
			$email = $value['email'];
			$name = $value['name'];
			$lastname = $value['lastname'];
			$string = $name . " " . $lastname . " " . $email . "<br>";
			array_push($student_array, $string);
		}

		$student = $_SESSION["student"];
		$purchase_order = $_SESSION['purchase_order'];
		$type = gettype($student);
		$student_count = count($student);
		$array_key = array();

		foreach ($student as $key => $value) {
			$schedule = getScheduleCode($purchasedCourseWooCommerce[0]['course_code'], $purchasedCourseWooCommerce[0]['course_plan'], $value['date']);
			$name_meta =  '_student_name' . $key;
			$lastname_meta = '_student_last_name' . $key;
			$email_meta = '_student_email' . $key;
			$available_date_meta = '_available_dates' . $key;
			$array_key[$name_meta] = $value['name'];
			$array_key[$lastname_meta] = $value['lastname'];
			$array_key[$email_meta] = $value['email'];
			$array_key[$available_date_meta] = $value['date'];
			$array_key['_dob' . $key] = $value['dob'];
			$array_key[$name_meta] = $value['name'];
			$array_key[$lastname_meta] = $value['lastname'];
			$array_key[$email_meta] = $value['email'];
			$array_key[$available_date_meta] = $value['date'];
			$array_key[$dob_key] = $dob;
			$plan_id = '_plan' . $key;
			$course_code = '_course' . $key;
			$array_key[$plan_id] =  $purchasedCourseWooCommerce[0]['course_plan'];
			$array_key[$course_code] =  $course_id;
			$course_id = '_course_id' . $key;
			$course_plan = '_course_plan' . $key;
			$course_schedule = '_course_schedule' . $key;

			$course_detail =  get_rtodata(['code' =>  $purchasedCourseWooCommerce[0]['course_code'], 'plan' => $purchasedCourseWooCommerce[0]['course_plan']], 'detail');
			$array_key[$course_id] = $course_detail['data']['plan']['course_id'];

			$array_key[$course_plan] = $purchasedCourseWooCommerce[0]['course_plan'];

			$array_key[$course_schedule] = $schedule;






			$student_link = '_students_link' . $key;
			$link = 'https://amstraining.com.au/enrolments/?code=' . $purchasedCourseWooCommerce[0]['course_code'] . '&plan=' . $purchasedCourseWooCommerce[0]['course_plan'] . '&schedule=' . $schedule;
			$array_key[$student_link] = $link;
		}

		$number_students = 'number_students';
		$array_key[$number_students] = $student_count;
		$purchase_order_key = 'purchase_order';
		$array_key[$purchase_order_key] = $purchase_order;
		$course_name_key = 'course_name';
		$array_key[$course_name_key] = $course_name;




		foreach ($array_key as $meta_key => $meta_value) {
			//$order->update_meta_data( $meta_key, $meta_value );
		}
		//	enrollStudent($value['name'],$value['lastname'],$dob, $value['email'],$course_detail['data']['plan']['course_id'],$purchasedCourseWooCommerce[0]['course_plan'],$schedule);



		// $order->update_meta_data( '_student_phone', esc_attr( $_POST['student_phone']) );
		// Save the custom checkout field value as user meta data
		if ($order->get_customer_id())
			foreach ($student as $key => $value) {
				$email = $value['email'];
				$name = $value['name'];
				$lastname = $value['lastname'];
				update_user_meta($order->get_customer_id(), 'student_name' . $key, esc_attr($name));
				update_user_meta($order->get_customer_id(), 'student_last_name' . $key, esc_attr($lastname));
				update_user_meta($order->get_customer_id(), 'student_email' . $key, esc_attr($email));
				// update_user_meta( $order->get_customer_id(), 'student_phone', esc_attr( $_POST['student_phone'] ) );
			}
	}
	$_SESSION['purchase_order'] = '';
}

add_filter('woocommerce_checkout_order_review_heading', 'change_order_review_heading');
function change_order_review_heading($heading)
{
	return 'New Order Review Heading';
}


function include_enrolment_shortcut_with_scripts_shortcode()
{
	ob_start(); // Start output buffering
	include 'template-parts/enrolments/enrolments.php';
?>

<?php

	return ob_get_clean(); // Get and clean the buffered output
}

add_shortcode('enrolment_shortcut', 'include_enrolment_shortcut_with_scripts_shortcode');

// Define a custom function to run when payment is completed
function custom_payment_complete_function($order_id)
{
	$order = wc_get_order($order_id);
	$order_data = $order->get_data(); // The Order data
	$number_students = $order->get_meta('number_students');
	$course_name =  $order->get_meta('course_name');
	$course_name = preg_replace('/[^a-zA-Z0-9\s$\/]/', '', $course_name);
	$purchase_order =  $order->get_meta('_payment_method');
	$billing_first_name = $order_data['billing']['first_name'];
	$billing_last_name = $order_data['billing']['last_name'];
	$name_meta = $billing_first_name . " " . $billing_last_name;
	$billing_email = $order_data['billing']['email'];
	$email_address = $billing_email;
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Cc: sales@developer.amstraining.com.au' // CC recipient's email
	);

	//$email_address = 'developer@twomoonsconsulting.com.au'; // Recipient's email address
	$subject = ' Training Company Enrolment Link (Please complete3)';
	//$message = 'Hello, here is the link to complete your  Training Company enrolment: [insert link here]';

	//wp_mail($email_address, $subject, $message, $headers);

	// Now you can use these variables as needed in your application

	if ($purchase_order !== 'igfw_invoice_gateway') {
		$length = 10; // Length of the random text
		$randomText = '';
		$order->update_status('completed');
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$owner = $order_data['billing']['company'];
		session_start();

		for ($i = 0; $i < $length; $i++) {
			$randomText .= $characters[rand(0, strlen($characters) - 1)];
		}
		global $wpdb;

		$table_name = $wpdb->prefix . 'valid_student_list'; // Replace 'custom_table' with your table name

		$data = array(
			'secret_key' => $randomText,
		);
		$wpdb->insert($table_name, $data);
		session_start();



		$message = '';
		$length = 32; // Length of the secret key in bytes (256 bits)
		$secretKey = bin2hex(random_bytes($length));
		global $wpdb;

		// Define your custom table name
		$table_name = $wpdb->prefix . 'secret_keys';

		// Prepare the data you want to insert
		$data = array(
			'secret_key' => $secretKey,

		);

		$link = "<br> Please use the following link to complete your enrolment:" . " " . $order->get_meta('_students_link');
		$message = "Dear " . $name_meta . ", <br> <br> You have been registered by " . $owner . " in the following course " . $course_name . "<br>" . $link . "&secretKey=" . $secretKey . "<br><br>For full course details and entry requirements please visit <a href='https://developer.amstraining.com.au'>www.developer.amstraining.com.au </a><br> <br> Kind regards,<br> Ian Cole<br>Operations Manager<br>  Training Company";
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Cc: sales@developer.amstraining.com.au' // CC recipient's email
		);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Cc: sales@developer.amstraining.com.au' // CC recipient's email
		);


		//   wp_mail($email_address, ' Training Company Enrolment Link (Please complete3) ' , $message,$headers);



	}
}
add_action('woocommerce_payment_complete', 'custom_payment_complete_function');

function custom_login_form_shortcode()
{
	ob_start();
	wp_login_form();
	return ob_get_clean();
}
add_shortcode('custom_login_form', 'custom_login_form_shortcode');
function enrollStudent($first_name, $last_name, $date_of_birth, $email_address, $course_id, $plan_id, $schdule_id, $start_date, $end_date)
{

	//	$apiResult = get_rtodata(array('values' => http_build_query($data)), 'enrol', 'Origin: Website Enrolment');

	$data = array(
		'first_name' => $first_name,
		'last_name' => $last_name,
		'date_of_birth' => $date_of_birth,
		'course_id' => $course_id,
		'plan_id' => $plan_id,
		'schedule_id' => $schdule_id,
		'email' => $email_address,
		'start_date' => $start_date,
		'end_date' => $end_date,
		'null' => null,
		'php' => 'hypertext processor'
	);
	$apiResult = get_rtodata(array('values' => http_build_query($data)), 'enrol', 'Origin: Website Enrolment');

	//Update the payment:
	$person_id = $apiResult['payment_data']['learner_id'];
	$enrolment_id = $apiResult['payment_data']['enrolment_id'];
	$payment_plan_id = $apiResult['payment_data']['payment_plan_id'];
	$payment_received = $apiResult['payment_data']['payment_due'];
	$course_id = $apiResult['payment_data']['course_id'];
	$plan_id = $apiResult['payment_data']['plan_id'];
	$start_date = $apiResult['payment_data']['start_date'];
	$end_date = $apiResult['payment_data']['end_date'];

	updatePayment($person_id, $enrolment_id, $payment_plan_id, $payment_received, $course_id, $plan_id, $start_date, $end_date);
}
function updatePayment($person_id, $enrolment_id, $payment_plan_id, $payment_received, $course_id, $plan_id, $start_date, $end_date)
{

	$apiResult = get_rtodata(array(
		'type' => 'payment',
		'person_id' => $person_id,
		'enrolment_id' => $enrolment_id,
		'payment_plan_id' => $payment_plan_id,
		'payment_received' => $payment_received,
		'data' => array(
			//'unit_id'=>$unit_id,
			'enrolment_id' => $enrolment_id,
			'course_id' => $course_id,
			'plan_id' => $plan_id,
			'start_date' => $start_date,
			'end_date' => $end_date,
			//'outcome_code'=>$outcome_code,
			//'outcome_date'=>$outcome_date
		)

	), 'update');
}

add_action('wp_ajax_add_secret_key', 'add_secret_key');

// Register AJAX callback for non-authenticated users
add_action('wp_ajax_nopriv_add_secret_key', 'add_secret_key');

function add_secret_key()
{

	// $parameter1 = $_POST['parameter1'];
	$secret_key = $_SESSION['secret_key'];

	global $wpdb;
	$table_name = $wpdb->prefix . 'secret_keys';

	// Prepare the data you want to insert
	$data = array(
		'secret_key' =>   $secret_key,

	);

	// Insert the data into the custom table
	$result = $wpdb->insert($table_name, $data);

	echo $secret_key;
}


add_action('wp_ajax_registration_checker', 'registration_checker');

// Register AJAX callback for non-authenticated users
add_action('wp_ajax_nopriv_registration_checker', 'registration_checker');

// AJAX callback function
function registration_checker()
{
	// Process the AJAX request
	$parameter1 = $_POST['parameter1'];
	global $wpdb;

	// Define your custom table name
	$table_name = $wpdb->prefix . 'secret_keys';

	// Define the column and value to check
	$column_name = 'secret_key';
	$value =  $parameter1;
	$_SESSION['secret_key'] = $value;

	// Prepare the SQL query
	$query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $column_name = %s", $value);

	// Execute the query
	$result = $wpdb->get_var($query);

	// Check if the record exists
	if ($result > 0) {
		echo 'True';
	} else {
		// echo 'Record does not exist.';
		global $wpdb;
		$table_name = $wpdb->prefix . 'secret_keys';

		// Prepare the data you want to insert
		$data = array(
			'secret_key' =>  $value,

		);
		//echo 'False';
		// Insert the data into the custom table
		//$result = $wpdb->insert($table_name, $data);









	}

	// Perform actions based on the request
	// ...

	// Return the response
	//echo  $parameter1;

	// Always exit after processing AJAX requests
	wp_die();
}
function registration_checker_shortcode()
{
	ob_start(); // Start output buffering

?>
	<script>
		jQuery(document).ready(function($) {
			//    alert('3081');
			$.ajax({
				url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX endpoint
				type: 'POST',
				data: {
					action: 'add_secret_key', // AJAX action name
				},
				success: function(response) {
					// Handle the AJAX response
					// alert(response);
					if (response === "True") {
						// Your code for a successful response
						// You can modify this section as needed
					} else {
						// Your code for an unauthorized response
						// You can modify this section as needed
					}
				},
				error: function(xhr, status, error) {
					// Handle AJAX error
					console.log(error);
				}
			});
		});
	</script>
<?php

	$output = ob_get_clean(); // Get the buffered output
	return $output;
}
add_shortcode('registration_checker_shortcode', 'registration_checker_shortcode');
// Add this code to your theme's functions.php file or a custom plugin.

function my_ajax_function7()
{
	// $current_url = $_POST['current_url'];
	$parameter1 = $_POST['parameter1'];
	if ($parameter1 === "") {
		wp_send_json_success("Empty");
		return;
	}
	// You can now use $current_url as needed.
	global $wpdb;

	$desired_meta_value = $parameter1;  // Replace with the meta_value you want to search for

	$sql = $wpdb->prepare(
		"SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_value = %s",
		$desired_meta_value
	);

	$results = $wpdb->get_results($sql);
	$valid_email = false;
	if ($results) {
		foreach ($results as $result) {
			$post_id = $result->post_id;
			$meta_key = $result->meta_key;
			$valid_email = true;
			// Output post_id and meta_key
			//  echo "Post ID: $post_id, Meta Key: $meta_key<br>";

			// You can use $post_id and $meta_key for further processing if needed
		}
	} else {
		// No records found with the desired meta_value in wp_postmeta
	}

	// For example, you can return it as a JSON response.
	wp_send_json_success($valid_email);
}
add_action('wp_ajax_my_action_check_enrol', 'my_ajax_function7');
add_action('wp_ajax_nopriv_my_action_check_enrol', 'my_ajax_function7'); // For non-logged-in users.

function face_to_face_auto_enrol($order_id)
{
	// Your custom code here

	// Example: Get the order object
	$order = wc_get_order($order_id);

	$number_students = $order->get_meta('number_students');
	for ($i = 0; $i <= $number_students - 1; $i++) {
		$name_meta =  '_student_name' . $i;
		$name_meta = $order->get_meta($name_meta);
		$lastname_meta = '_student_last_name' . $i;
		$last_name = $order->get_meta($lastname_meta);
		$email_meta = '_student_email' . $i;
		$email = $order->get_meta($email_meta);
		$available_date_meta = '_available_dates' . $i;
		$available_date = $order->get_meta($available_date_meta);

		if ($available_date !== "Empty") {
			$dateRange = $available_date;
			$count =  substr_count($dateRange, '-');
			if ($count == 2) {
				$dateRange = $dateRange . "-" . $dateRange;
			}
			$start_date_str = substr($dateRange, 0, 10);
			$end_date_str = substr($dateRange, 11);
			$start_date = DateTime::createFromFormat('d-m-Y', $start_date_str);
			$end_date = DateTime::createFromFormat('d-m-Y', $end_date_str);

			// Format the dates as needed
			$start_date_formatted = $start_date->format('d-m-Y');
			$end_date_formatted = $end_date->format('d-m-Y');
			$course_id = '_course_id' . $i;
			$course_id = $order->get_meta($course_id);
			$course_plan = '_course_plan' . $i;
			$course_plan =  $order->get_meta($course_plan);
			$course_schedule = '_course_schedule' . $i;
			$course_schedule = $order->get_meta($course_schedule);
			$dob = '_dob' . $i;
			$dob = $order->get_meta($dob);
			enrollStudent($name_meta, $last_name, $dob, $email, $course_id, $course_plan, $course_schedule, $start_date_formatted, $end_date_formatted);
		}
	}
}
add_action('woocommerce_order_status_completed', 'face_to_face_auto_enrol', 10, 1);

// Add jQuery AJAX code to wp_footer hook
function my_custom_ajax_script()
{
?>
	<script>
		// Get the element
		document.addEventListener('DOMContentLoaded', function() {
			const studentNameInput = document.getElementById('student_name');
			const studentLastName = document.getElementById('student_name_last_name');
			const studentEmail = document.getElementById('student_email_adress');
			studentNameInput.addEventListener('click', function() {
				// const studentName = studentNameInput.value;
				//  alert('Student Name:', studentName);
				const errorMessageSpans = document.querySelectorAll('span.error-message');
				studentNameInput.style = "border-color:none!important";
				if (errorMessageSpans.length > 0) {
					//  alert('At least one error message span exists!');
					// Do something if at least one error message span exists
					errorMessageSpans.forEach(function(span) {
						span.remove();
					});
				} else {
					//  alert('No error message spans exist.');
					// Do something else if no error message spans exist
				}
				// You can perform any actions with the student name here
			});



			studentLastName.addEventListener('click', function() {
				// const studentName = studentNameInput.value;
				//  alert('Student Name:', studentName);
				const errorMessageSpans = document.querySelectorAll('span.error-message');
				studentLastName.style = "border-color:none!important";
				if (errorMessageSpans.length > 0) {
					//  alert('At least one error message span exists!');
					// Do something if at least one error message span exists
					errorMessageSpans.forEach(function(span) {
						span.remove();
					});
				} else {
					//  alert('No error message spans exist.');
					// Do something else if no error message spans exist
				}
				// You can perform any actions with the student name here
			});



			studentEmail.addEventListener('click', function() {
				// const studentName = studentNameInput.value;
				//  alert('Student Name:', studentName);
				const errorMessageSpans = document.querySelectorAll('span.error-message');
				studentEmail.style = "border-color:none!important";
				if (errorMessageSpans.length > 0) {
					//  alert('At least one error message span exists!');
					// Do something if at least one error message span exists
					errorMessageSpans.forEach(function(span) {
						span.remove();
					});
				} else {
					//  alert('No error message spans exist.');
					// Do something else if no error message spans exist
				}
				// You can perform any actions with the student name here
			});






		});
	</script>

	<script>
		$('body').on('click', '#place_order', async function(e) {
			// Prevent the default form submission

			// alert('3505');
			e.preventDefault();
			var quantity = document.getElementById('student_quantity').value;
			var $form = $('form.checkout');

			function isValidEmail(email) {
				var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailRegex.test(email);
			}

			// Function to create error message and apply red border to field
			function createErrorMessage(fieldId, message) {
				$(fieldId).after('<span class="error-message">' + message + '</span>');
				$(fieldId).addClass('error-field'); // Add red border class
			}

			function checkPaymentType() {
				var $form = $('form.checkout');
				//Figuring out which purchase type was choosen.
				var checkboxId;
				//alert('3512');


				if ($('#po_checkbox').length) // use this if you are using id to check
				{

					if ($('#po_checkbox').is(":checked")) {
						checkboxId = "po_checkbox";
					}
				}
				if ($('#sa_checkbox').length) // use this if you are using id to check
				{

					if ($('#sa_checkbox').is(":checked")) {
						checkboxId = "sa_checkbox";
					}
				}

				if ($('#payment_method_pin_payments').length) // use this if you are using id to check
				{

					if ($('#payment_method_pin_payments').is(":checked")) {
						checkboxId = "stripe";
						//alert(checkboxId);
						console.log(checkboxId);
					}
				}
				//alert(checkboxId);		
				if (checkboxId == "stripe") {
					//alert('3649');
					//e.preventDefault();
					//  modal
					// document.getElementById('modal').style = "display:block";
					$form.submit();
				}
				if (checkboxId == "sa_checkbox") {
					// alert('clicked 1359');
					$.ajax({
						url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX handler URL
						type: 'POST',
						data: {
							action: 'standing_agrement_number', // PHP function to handle the request
							// additional parameters if needed
						},
						success: function(response) {
							// Handle the response from the server
							//   alert(response);
							var standing_agreement = (document.getElementById("igfw_standard_order_number").value);
							//alert(standing_agreement);
							if (standing_agreement === response) {
								//   alert('Valid');
								//   resolve('valid');
								$form.submit();
							} else {
								// createSpan('igfw_standing_agreement_number','error_standing_agreement_number','Please enter standing agreement number');
								//  resolve('Invalid');
							}


						}
					});

				}
				if (checkboxId == "po_checkbox") {

					var file_data = $('#file1').prop('files')[0];
					var input = document.querySelector('#igfw_purchase_order_number');
					var value = input.value;
					//  alert(value);
					var form_data = new FormData();
					form_data.append('file', file_data);
					form_data.append('action', 'custom_file_upload');
					form_data.append('purchase_order', value);
					form_data.append('studentInfo', JSON.stringify(sessionStorage.getItem('studentInfo')));
					//alert('3585');
					$.ajax({
						url: 'https://amstraining.com.au/wp-admin/admin-ajax.php',
						type: 'POST',
						data: form_data,
						processData: false,
						contentType: false,
						xhr: function() {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener('progress', function(evt) {
								if (evt.lengthComputable) {
									var percentComplete = evt.loaded / evt.total * 100;
									$('#upload-status').html('Uploading: ' + percentComplete.toFixed(0) + '%');
								}
							}, false);
							return xhr;
						},
						success: function(response) {
							// Handle the response after the file is uploaded
							//  alert(response);
							$('#upload-status').html('Upload Complete');
							//  alert('1434');
							$form.submit();

						}
					});
				}


















			}

			// alert(checkboxId);
			// var $form = $('form.checkout');
			// Remove any existing error messages and red borders
			$('.error-message').remove();
			$('.error-field').removeClass('error-field');
			var numStudents = document.getElementById('student_quantity').value;
			// Perform validation for each student
			for (var i = 1; i <= numStudents; i++) {
				var firstName = $('#student_first_name_' + i).val();
				var lastName = $('#student_last_name_' + i).val();
				var email = $('#student_email_' + i).val();
				var errorFound = false;

				// Check if any field is empty
				if (!firstName) {
					createErrorMessage('#student_first_name_' + i, 'Please enter first name for Student ' + i);
					errorFound = true;
				}
				if (!lastName) {
					createErrorMessage('#student_last_name_' + i, 'Please enter last name for Student ' + i);
					errorFound = true;
				}
				if (!email) {
					createErrorMessage('#student_email_' + i, 'Please enter email for Student ' + i);
					errorFound = true;
				}
				// Perform additional validation checks if needed (e.g., email format)
				if (email && !isValidEmail(email)) {
					createErrorMessage('#student_email_' + i, 'Please enter a valid email for Student ' + i);
					errorFound = true;
				}

				if (errorFound) {
					return; // Stop further processing
				}
			}
			checkPaymentType();


			sessionStorage.setItem("checkboxId", "");
		});
	</script>
	<script>
		$('body').on('click', '#place_order2', async function(e) {
			var counter = 1; // Initial counter value
			e.preventDefault();

			function validateAndIncreaseCounter(N) {

				//alert(N);
				for (var i = 1; i <= N; i++) {
					var firstNameInputId = "student_first_name_" + i;
					var lastNameInputId = "student_last_name_" + i;
					var emailInputId = "student_email_" + i;
					alert(i);
					var firstName = $("#" + firstNameInputId).val();
					var lastName = $("#" + lastNameInputId).val();
					var email = $("#" + emailInputId).val();

					// Validation for First Name
					if (firstName === "") {
						showError(firstNameInputId, "Please enter your first name.");
						return false;
					}
					if (!/^[A-Za-z]+$/.test(firstName)) {
						showError(firstNameInputId, "First name should contain only letters.");
						return false;
					}

					// Validation for Last Name
					if (lastName === "") {
						showError(lastNameInputId, "Please enter your last name.");
						return false;
					}
					if (!/^[A-Za-z]+$/.test(lastName)) {
						showError(lastNameInputId, "Last name should contain only letters.");
						return false;
					}

					// Validation for Email Address
					if (email === "") {
						showError(emailInputId, "Please enter your email address.");
						return false;
					}
					if (!/\S+@\S+\.\S+/.test(email)) {
						showError(emailInputId, "Please enter a valid email address.");
						return false;
					}
					alert('3311');
					// Increase counter for next iteration

				}

				// All validations and counter increases completed N times
				//alert("Validation and counter increase completed " + N + " times.");

				// Optionally, reset form if needed
				document.getElementById("myForm").reset();

				return true;
			}

			function showError(inputId, message) {
				//alert(inputId);
				//$("#" + inputId).next(".error-message").text(message).show();
				// $('<span id="errorSpan" class="error-message">' +message + '</span>').insertAfter('#'+inputId);





			}

			validateAndIncreaseCounter(5);














		});
	</script>
	<script>
		$('body').on('click', '#place_order1', async function(e) {
			// Prevent the default form submission

			// alert('3505');

			var student_list = document.getElementById("student-list");
			if (student_list.style.display === "none") {
				//alert('error');
				var input = document.getElementById("add_next_student");
				var name = 'Please click save student before submitting the form. Your credit card has not been charged yet.';
				var span_element = document.getElementById("error-message-add-students");
				// Create a new span element
				if (!span_element) {
					var span = document.createElement("span");
					// Set the text content of the span element
					span.textContent = name;
					span.style = "color:red!important;";
					span.className = "error-message";
					span.id = "error-message-add-students";
					// Append the span element after the input
					input.parentNode.insertBefore(span, input.nextSibling);

					document.getElementById("add_next_student").style = "border-color:red!important;";

				} else {
					span_element.textContent = name;
					span_element.style = "color:red!important;";
					span_element.className = "error-message";
					span_element.id = "error-message-add-students";
					// Append the span element after the input
					input.parentNode.insertBefore(span, input.nextSibling);

				}
				// e.preventDefault();
				// return;
			}
			var $form = $('form.checkout');

			function checkPaymentType() {
				var $form = $('form.checkout');
				//Figuring out which purchase type was choosen.
				var checkboxId;
				//alert('3512');


				if ($('#po_checkbox').length) // use this if you are using id to check
				{

					if ($('#po_checkbox').is(":checked")) {
						checkboxId = "po_checkbox";
					}
				}
				if ($('#sa_checkbox').length) // use this if you are using id to check
				{

					if ($('#sa_checkbox').is(":checked")) {
						checkboxId = "sa_checkbox";
					}
				}

				if ($('#payment_method_cpsw_stripe').length) // use this if you are using id to check
				{

					if ($('#payment_method_cpsw_stripe').is(":checked")) {
						checkboxId = "stripe";
						//	alert(checkboxId);
						console.log(checkboxId);
					}
				}
				//alert(checkboxId);		
				if (checkboxId == "stripe") {
					//alert('1365');
					//e.preventDefault();
					//  modal
					// document.getElementById('modal').style = "display:block";
					$form.submit();
				}
				if (checkboxId == "sa_checkbox") {
					// alert('clicked 1359');
					$.ajax({
						url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX handler URL
						type: 'POST',
						data: {
							action: 'standing_agrement_number', // PHP function to handle the request
							// additional parameters if needed
						},
						success: function(response) {
							// Handle the response from the server
							//   alert(response);
							var standing_agreement = (document.getElementById("igfw_standard_order_number").value);
							//alert(standing_agreement);
							if (standing_agreement === response) {
								//   alert('Valid');
								//   resolve('valid');
								$form.submit();
							} else {
								// createSpan('igfw_standing_agreement_number','error_standing_agreement_number','Please enter standing agreement number');
								//  resolve('Invalid');
							}


						}
					});

				}
				if (checkboxId == "po_checkbox") {

					var file_data = $('#file1').prop('files')[0];
					var input = document.querySelector('#igfw_purchase_order_number');
					var value = input.value;
					//  alert(value);
					var form_data = new FormData();
					form_data.append('file', file_data);
					form_data.append('action', 'custom_file_upload');
					form_data.append('purchase_order', value);
					form_data.append('studentInfo', JSON.stringify(sessionStorage.getItem('studentInfo')));
					//alert('3585');
					$.ajax({
						url: 'https://amstraining.com.au/wp-admin/admin-ajax.php',
						type: 'POST',
						data: form_data,
						processData: false,
						contentType: false,
						xhr: function() {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener('progress', function(evt) {
								if (evt.lengthComputable) {
									var percentComplete = evt.loaded / evt.total * 100;
									$('#upload-status').html('Uploading: ' + percentComplete.toFixed(0) + '%');
								}
							}, false);
							return xhr;
						},
						success: function(response) {
							// Handle the response after the file is uploaded
							//  alert(response);
							$('#upload-status').html('Upload Complete');
							//  alert('1434');
							$form.submit();

						}
					});
				}


















			}

			// alert(checkboxId);
			// var $form = $('form.checkout');
			checkPaymentType();


			sessionStorage.setItem("checkboxId", "");
		});
	</script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$.ajax({
				url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX URL
				type: 'POST',
				data: {
					action: 'custom_user_check' // Updated action name
				},
				success: function(response) {
					var data = JSON.parse(response);
					if (data.logged_in) {
						// User is logged in
						//  alert('User is logged in');
						console.log("3918");
						console.log(document.getElementById('po_checkbox').checked);
						document.getElementById("po_checkbox").checked();
					} else {
						// User is not logged in
						//   window.location.href = 'https://amstraining.com.au/';

						document.getElementById("payment_method_cpsw_stripe").click();
					}
				},
				error: function(xhr, status, error) {
					console.error(xhr.responseText);
				}
			});
		});
	</script>
	<script>
		$(document).ready(function() {
			// Add event listener to the body for change events on checkboxes
			$('body').on('change', 'input[type="radio"]', function() {
				if ($(this).is(':checked')) {
					// Radio button is checked, do something
					var checkboxId = $(this).attr('id');
					if (checkboxId == "payment_method_pin_payments") {
						// Select the div with class "payment_box" and "payment_method_pin_payments"
						var paymentDiv = document.querySelector('.payment_box.payment_method_pin_payments');

						// Check if the div is found
						if (paymentDiv) {
							// Change the display property to "block"
							console.log("Pin Payment");

							//document.getElementById('po_checkbox').click();
							paymentDiv.style.display = "block";
						} else {
							console.log("Payment div not found.");
						}

					}
				}
			});

			$('body').on('change', 'input[type="checkbox"]', function() {
				var checkboxId = $(this).attr('id');
				console.log(checkboxId);

				if ($(this).is(':checked')) {
					if (checkboxId == "po_checkbox") {

						document.getElementById('payment_method_igfw_invoice_gateway').click();
						document.getElementById('purchase-order-number').style = "display:block";
						sessionStorage.setItem("checkboxId", "po_checkbox");
						document.getElementById('standard-order-number').style = "display:none";
						document.getElementById("sa_checkbox").checked = false;
					}
					if (checkboxId == "sa_checkbox") {
						sessionStorage.setItem("checkboxId", "sa_checkbox");
						document.getElementById('standard-order-number').style = "display:block";
						document.getElementById('purchase-order-number').style = "display:none";
						document.getElementById("po_checkbox").checked = false;
						document.getElementById('payment_method_igfw_invoice_gateway').click();
					}
				} else {
					if (checkboxId == "po_checkbox") {
						document.getElementById('purchase-order-number').style = "display:none";

					}
					if (checkboxId == "sa_checkbox") {
						document.getElementById('standard-order-number').style = "display:none";
					}
				}
			});
		});
	</script>

	<?php
}
add_action('wp_footer', 'my_custom_ajax_script');

// Add custom fields before add to cart button

// Define a callback function to modify the quantity input field
// Add custom checkout fields
add_action('woocommerce_before_order_notes', 'add_custom_checkout_fields');

function add_custom_checkout_fields($checkout)
{
}

// Save custom checkout field data for dynamic fields
// Save custom checkout field data for dynamic fields
// Save custom checkout field data for dynamic fields
// Save custom checkout field data for dynamic fields based on quantity
// Save custom checkout field data for dynamic fields
add_action('woocommerce_checkout_update_order_meta', 'save_custom_checkout_field_data_for_dynamic_fields');

function save_custom_checkout_field_data_for_dynamic_fields($order_id)
{
	// Counter for generating unique IDs
	$counter = 1;
	$student_quantity = sanitize_text_field($_POST['student_quantity']);
	update_post_meta($order_id, 'student_quantity', $student_quantity);
	// Loop through all dynamic fields
	while (isset($_POST['student_first_name_' . $counter])) {
		$student_first_name = sanitize_text_field($_POST['student_first_name_' . $counter]);
		$student_last_name = sanitize_text_field($_POST['student_last_name_' . $counter]);
		$student_email = sanitize_email($_POST['student_email_' . $counter]);


		//student_quantity
		// Save data for each set of dynamic fields
		if ($student_first_name && $student_last_name && $student_email) {
			update_post_meta($order_id, 'Student_First_Name_' . $counter, $student_first_name);
			update_post_meta($order_id, 'Student_Last_Name_' . $counter, $student_last_name);
			update_post_meta($order_id, 'Student_Email_' . $counter, $student_email);
		}

		// Increment counter
		$counter++;
	}
}


// Display custom fields in order details for dynamic fields


add_action('wp_footer', 'update_cart_on_item_qty_change');
function update_cart_on_item_qty_change()
{
	if (is_cart()) :
	?>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$.ajax({
					url: 'https://amstraining.com.au/wp-admin/admin-ajax.php', // WordPress AJAX URL
					type: 'POST',
					data: {
						action: 'custom_user_check' // Updated action name
					},
					success: function(response) {
						var data = JSON.parse(response);
						if (data.logged_in) {
							// User is logged in
							console.log('User is logged in');
							document.getElementById('po_checkbox').checked = true;

						} else {
							// User is not logged in
							//window.location.href = 'https://amstraining.com.au/';

							document.getElementById("payment_method_pin_payments").click();
						}
					},
					error: function(xhr, status, error) {
						console.error(xhr.responseText);
					}
				});
			});
		</script>
		<script>
			// Get the current URL
			var currentURL = window.location.href;
			var valid = false;
			// Check if the current URL matches a specific URL
			if (currentURL === "https://amstraining.com.au/") {
				//  alert("You are on Page 1!");
				valid = true;
			}
			if (currentURL === "https://amstraining.com.au/online-refresher-courses/work-safely-at-heights-online-refresher-riiwhs204e/") {
				//  alert("You are on Page 1!");
				valid = true;
			} else if (currentURL === "https://amstraining.com.au/online-refresher-courses/confined-space-online-refresher-riiwhs202e-2/") {
				// alert("You are on Page 2!");
				valid = true;
			} else if (currentURL === "https://amstraining.com.au/online-refresher-courses/gas-test-atmospheres-online-refresher-msmwhs217-1/") {
				// alert("You are on a different page!");
				valid = true;
			} else if (currentURL === "https://amstraining.com.au/online-refresher-courses/riiwhs202e-enter-and-work-in-confined-spaces-msmwhs217-gas-test-atmospheres-online-refresher/") {
				// alert("You are on a different page!");
				valid = true;
			} else if (currentURL === "https://amstraining.com.au/online-refresher-courses/confined-space-and-working-at-heights-online-refresher-riiwhs202e-3/") {
				// alert("You are on a different page!");
				valid = true;

			} else if (currentURL === "https://amstraining.com.au/online-refresher-courses/confined-space-working-at-heights-gas-test-online-refresher-riiwhs202e-4/") {
				//  alert("You are on a different page!");
				valid = true;

			} else if (currentURL === "https://amstraining.com.au/student-info/") {
				// alert("You are on a different page!");
				valid = true;

			} else if (currentURL === "https://amstraining.com.au/about/") {
				// alert("You are on a different page!");
				valid = true;

			} else if (currentURL === "https://amstraining.com.au/contact/") {
				//alert("You are on a different page!");
				valid = true;

			} else if (currentURL === "https://amstraining.com.au/") {
				//   alert("You are on a different page!");
				valid = true;
			} else if (currentURL.includes("checkout")) {
				//   alert("You are on a different page!");
				valid = true;
			}
			//https://amstraining.com.au/paymentprocess-load-gateway/?code=RIIWHS204E-2&plan=38&course_title=RIIWHS204E%20Work%20Safely%20at%20Heights%20-%20Online%20Refresher%20Course
			if (currentURL.includes("https://amstraining.com.au/paymentprocess-load-gateway/")) {
				valid = true;
			}
			if (currentURL.includes("enrolments")) {
				valid = true;
			}

			if (valid === false) {
				window.location.href = "https://amstraining.com.au/";
			}
		</script>
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				// This function will run when the document has finished loading

				document.getElementById('student_quantity').value = 1;
				// You can put any JavaScript code you want to run here
			});
		</script>
		<script type="text/javascript">
			jQuery(function($) {
				let counter = 1;

				// Function to clone fields
				function cloneFields(q, p) {
					const originalFields = document.querySelectorAll('.student-details');
					//  alert(q);
					counter = p;
					// Clone the last set of fields
					const counterParagraph = document.createElement('p');
					counterParagraph.textContent = 'Student Number: ' + counter;
					counterParagraph.className = 'Student_Number';
					document.getElementById('student-details-container').appendChild(counterParagraph);
					const clonedFields = originalFields[originalFields.length - 1].cloneNode(true);

					// Update IDs and names to be unique
					clonedFields.querySelectorAll('[id], [name]').forEach(field => {


						field.id = field.id + '_' + counter;
						field.name = field.name + '_' + counter;
						if (field.name.indexOf("student_last_name") !== -1) {
							field.id = "student_last_name" + '_' + counter;
							field.name = "student_last_name" + '_' + counter;
							field.value = "";

						}
						if (field.name.indexOf("student_first_name") !== -1) {
							field.id = "student_first_name" + '_' + counter;
							field.name = "student_first_name" + '_' + counter;
							field.value = "";

						}
						if (field.name.indexOf("student_email") !== -1) {
							field.id = "student_email" + '_' + counter;
							field.name = "student_email" + '_' + counter;
							field.value = "";


						}


					});

					// Append cloned fields

					document.getElementById('student-details-container').appendChild(clonedFields);

					// Increment counter


				}

				function duplicate_student(item_quantity) {

					//alert(item_quantity);
					var re = /(\d+\.\d+)/.exec($(".woocommerce-Price-amount").html());
					var price = re[0];

					// var price = re*item_quantity;
					var subTotalAmount = $('#sub-total .woocommerce-Price-amount.amount').text();

					var subTotalNumber = price * item_quantity;
					$('#sub-total .woocommerce-Price-amount.amount').text('$' + subTotalNumber);
					// alert('3586');
					//	alert(subTotalNumber);
					// $(".woocommerce-Price-amount").html("$"+ price);




					// alert();
					//$(".woocommerce-Price-amount").html("$"+price);
					document.getElementById('student_quantity').value = item_quantity;
					const clonedItemsCount = document.querySelectorAll('.student-details').length;
					// alert(item_quantity);
					//alert(clonedItemsCount);
					if (item_quantity > clonedItemsCount) {
						function callCloneFieldsNTimes(N) {
							let counter = clonedItemsCount + 1;
							for (let i = 0; i < N; i++) {

								cloneFields(i, counter);
								counter = counter + 1;
							}
						}
						let excess_student = item_quantity - clonedItemsCount;
						callCloneFieldsNTimes(excess_student);
						//cloneFields();
					} else if (clonedItemsCount > item_quantity) {
						function removeLastItem() {
							const clonedItems = document.querySelectorAll('.student-details');
							const lastClonedItem = clonedItems[clonedItems.length - 1];
							lastClonedItem.remove(); // Remove the last cloned item from the DOM

							$(".Student_Number:last").remove();
						}
						const N = clonedItemsCount - item_quantity;
						for (let i = 0; i < N; i++) {
							removeLastItem();
						}




					}

				}




				$(document.body).on('mouseenter click', '.qty', function() {
					//$('button[name="update_cart"]').trigger('click');
					//alert('qty');
					var item_quantity = $(this).val();
					if (item_quantity == 0) {
						$(this).val() = 1;
						return;
					}
					duplicate_student(item_quantity);













				});
				$(document.body).on('mouseenter click', '.grve-plus', function() {
					//$('button[name="update_cart"]').trigger('click');
					//  alert('plus');
					// Accessing an element with the class name 'qty' inside document.body
					var qtyElement = document.body.querySelector('.qty');

					// Checking if the element is found
					if (qtyElement) {
						// Accessing the value of the element
						var qtyValue = qtyElement.value;
						// alert(qtyValue);
					} else {
						console.log("Element with class 'qty' not found in the document body.");
					}
					var item_quantity = qtyValue;
					if (item_quantity == 0) {
						qtyElement.value = 1;
						return;
					}
					duplicate_student(item_quantity);












				});


				$(document.body).on('mouseenter click', '.grve-minus', function() {
					//$('button[name="update_cart"]').trigger('click');
					// alert('minus');
					// Accessing an element with the class name 'qty' inside document.body
					var qtyElement = document.body.querySelector('.qty');

					// Checking if the element is found
					if (qtyElement) {
						// Accessing the value of the element
						var qtyValue = qtyElement.value;
						// alert(qtyValue);
					} else {
						console.log("Element with class 'qty' not found in the document body.");
					}
					var item_quantity = qtyValue;
					if (item_quantity == 0) {
						qtyElement.value = 1;
						return;
					}
					duplicate_student(item_quantity);












				});




















			});
		</script>

	<?php
	endif;
}
// Add student details to WooCommerce order

// Define a function to update quantities before processing
function update_quantities_before_processing()
{
	// Check if it's the checkout page
	if (is_checkout()) {
		// Loop through each item in the cart
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			// Get the product ID
			$product_id = $cart_item['product_id'];
			$student_quantity = sanitize_text_field($_POST['student_quantity']);
			// Update the quantity as needed
			// For example, double the quantity of each product
			WC()->cart->set_quantity($cart_item_key, $cart_item['quantity'] * $student_quantity);
		}
	}
}
// Hook the function to the checkout process
add_action('woocommerce_checkout_process', 'update_quantities_before_processing');

// Define a custom function to run when an order is completed
function my_custom_order_completion_function($order_id)
{
	// Get the order object
	$order = wc_get_order($order_id);
	$student_number = $order->get_meta('student_quantity');
	$payment_type = $order->get_meta('_payment_method');
	// Perform actions here based on the completed order
	// For example, send a confirmation email or update a database record
	$link = "<br> Please use the following link to complete your enrolment:" . " " . $order->get_meta('_students_link');
	// Here's an example of sending a custom email to the customer
	$recipient = $order->get_billing_email();
	$subject = 'Your order is complete';
	$message = 'Thank you for your order. It has been successfully completed.';
	// sendEmail($order_id);
	if ($payment_type !== "igfw_invoice_gateway") {
		sendEmail($order_id);
	}



	//wp_mail( $recipient, $subject, $message );

	// You can add more actions or custom logic as needed

	// Note: Make sure to test thoroughly before deploying any changes to a live site
}
add_action('woocommerce_order_status_completed', 'my_custom_order_completion_function', 10, 1);

// Add new action to order actions dropdown
add_filter('woocommerce_order_actions', 'my_custom_woocommerce_order_actions', 10, 2);
add_action('woocommerce_process_shop_order_meta', 'my_custom_woocommerce_order_action_execute', 50, 2);

/**
 * Filter: woocommerce_order_actions
 * Allows filtering of the available order actions for an order.
 *
 * @param array $actions The available order actions for the order.
 * @param WC_Order|null $order The order object or null if no order is available.
 * @since 2.1.0 Filter was added.
 * @since 5.8.0 The $order param was added.
 */
function my_custom_woocommerce_order_actions($actions, $order)
{
	$actions['my-custom-order-action'] = __('Release Purchase Order Emails', 'my-custom-order-action');
	return $actions;
}

/**
 * Save meta box data.
 *
 * @param int $post_id Post ID.
 * @param WP_Post $post Post Object.
 */

/**
 * Action: woocommerce_order_action_my_custom_order_action
 * Executes when the custom order action is selected.
 *
 * @param int $order_id The order ID.
 */

function my_custom_woocommerce_order_action_execute(int $post_id, WP_Post $post)
{


	$order = wc_get_order();
	$order->add_order_note(__('My Custom Order Action was executed', 'my-custom-order-action'));
	if (filter_input(INPUT_POST, 'wc_order_action') !== 'my-custom-order-action') {
		return;
	}

	$order = wc_get_order($post_id);
	sendEmail($post_id);
	$order->add_order_note(__('My Custom Order Action was executed', 'my-custom-order-action'));
}
add_filter('default_checkout_billing_state', 'change_default_checkout_state');
add_filter('default_checkout_billing_state', 'change_default_checkout_state');
function change_default_checkout_state()
{
	return ''; //set state code if you want to set it otherwise leave it blank.
}
add_action('woocommerce_checkout_create_order_line_item', 'save_custom_cart_item_data_to_order_items', 10, 4);
function save_custom_cart_item_data_to_order_items($item, $cart_item_key, $values, $order)
{
	if (isset($values['enrolemnt_link'])) {
		$item->add_meta_data('enrolemnt_link', $values['enrolemnt_link']);
		$item->add_meta_data('course_name', $values['course_title']);
	}
}
add_action('woocommerce_thankyou', 'process_enrolemnt_link', 10, 1);
function process_enrolemnt_link($order_id)
{
	$order = wc_get_order($order_id);

	foreach ($order->get_items() as $item_id => $item) {
		$enrolemnt_link = $item->get_meta('enrolemnt_link');
		$course_name = $item->get_meta('course_name');
		if ($enrolemnt_link) {
			// Process the enrolment link
			// For example, send an email or perform an API request
			// Here, we'll just output it for demonstration
			echo '<p>Enrolment Link: ' . esc_html($enrolemnt_link) . '</p>';
			echo '<p>Course Link: ' . esc_html($course_name) . '</p>';
		}
	}
}
// Redirect 404 pages to homepage
function redirect_404_to_homepage()
{
	if (is_404()) {
		wp_redirect(home_url('/'));
		exit;
	}
}
add_action('template_redirect', 'redirect_404_to_homepage');
add_action('template_redirect', 'redirect_404_to_homepage');
// Register AJAX callback for authenticated users
add_action('wp_ajax_handle_secret_key', 'handle_secret_key');

// Register AJAX callback for non-authenticated users
add_action('wp_ajax_nopriv_handle_secret_key', 'handle_secret_key');

// AJAX callback function
function handle_secret_key()
{
	// Check if the request is valid
	$_POST['parameter1'] =  $_SESSION['secret_key'];
	if (!isset($_POST['parameter1'])) {
		echo 'Invalid request';
		wp_die();
	}

	// Sanitize the input
	$parameter1 = sanitize_text_field($_POST['parameter1']);


	global $wpdb;

	// Define your custom table name
	$table_name = $wpdb->prefix . 'secret_keys';

	// Define the column and value to check
	$column_name = 'secret_key';

	// Prepare the SQL query
	$query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $column_name = %s", $parameter1);

	// Execute the query
	$result = $wpdb->get_var($query);

	// Check if the record exists
	if ($result > 0) {
		// If the record exists, delete it
		$delete_query = $wpdb->prepare("DELETE FROM $table_name WHERE $column_name = %s", $parameter1);
		$wpdb->query($delete_query);
		echo 'Deleted';
	} else {
		// If the record does not exist, insert the new data
		$data = array(
			'secret_key' => $parameter1,
		);

		// Insert the data into the custom table
		$wpdb->insert($table_name, $data);

		echo 'Inserted';
	}

	// Always exit after processing AJAX requests
	wp_die();
}
// Register the shortcode
add_shortcode('secret_key_handler', 'secret_key_handler_shortcode');

// Shortcode callback function
function secret_key_handler_shortcode()
{
	ob_start(); // Start output buffering

	// Output the HTML and JavaScript code for the AJAX functionality
	?>


	<script>
		jQuery(document).ready(function($) {
			// Get the secret key from the input field
			var secretKey = $('#secret-key-input').val();

			// Make AJAX request on page load
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'POST',
				data: {
					action: 'handle_secret_key',
					parameter1: secretKey,
				},
				success: function(response) {

				},
				error: function(xhr, status, error) {
					console.log(xhr.responseText);
				}
			});
		});
	</script>
<?php

	return ob_get_clean(); // Return the buffered content
}

?>