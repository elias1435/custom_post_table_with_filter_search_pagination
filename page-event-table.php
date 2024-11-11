<?php
/* Template Name: Event Table Page */

get_header();  // Include the header

?>

<div class="main-event-wrapper">
<!-- Featured Image Section with Page Title -->
<div class="event-hero bg-cover bg-center" style="background-image: url('<?php echo get_the_post_thumbnail_url(); ?>');">
    <div class="event-title-overlay text-4xl content-center h-96 font-normal text-white text-center bg-black bg-opacity-50 py-4">
        <h1 class="font-normal text-white text-center uppercase"><?php the_title(); ?></h1>
    </div>
</div>
<div class="container mx-auto p-6">
<div class="event-description px-4 mt-4 mb-12">
    <?php the_content(); ?>
</div>
    <!-- Filter Form - Horizontally Aligned Dropdowns and Instant Search -->
    <form method="GET" class="mb-6" id="event-filters">
        <div class="flex space-x-6">
            <!-- Event Name Filter -->
            <div class="w-1/3">
                <label for="event_name" class="block font-semibold">Event Name</label>
                <select name="event_name" id="event_name" class="border px-4 py-2 rounded-md w-full">
                    <option value="">Select Event</option>
                    <?php
                    // Get all posts from 'event' post type for event name filtering
                    $event_posts = get_posts(array(
                        'post_type' => 'event',
                        'posts_per_page' => -1,  // Get all events
                    ));
                    foreach ($event_posts as $event) :
                        echo '<option value="' . esc_attr($event->ID) . '" ' . (isset($_GET['event_name']) && $_GET['event_name'] == $event->ID ? 'selected' : '') . '>' . esc_html($event->post_title) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>

            <!-- Location Filter -->
            <div class="w-1/3">
                <label for="event_location" class="block font-semibold">Location</label>
                <select name="event_location" id="event_location" class="border px-4 py-2 rounded-md w-full">
                    <option value="">Select Location</option>
                    <?php
                    // Get all distinct locations
                    global $wpdb;
                    $locations = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'event_location' AND meta_value != ''");
                    foreach ($locations as $location) :
                        echo '<option value="' . esc_attr($location) . '" ' . (isset($_GET['event_location']) && $_GET['event_location'] == $location ? 'selected' : '') . '>' . esc_html($location) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="w-1/3">
                <label for="event_category" class="block font-semibold">Category</label>
                <select name="event_category" id="event_category" class="border px-4 py-2 rounded-md w-full">
                    <option value="">Select Category</option>
                    <?php
                    // Get the event categories for the dropdown
                    $categories = get_terms(array(
                        'taxonomy' => 'event-category',
                        'orderby' => 'name',
                        'hide_empty' => false,
                    ));
                    foreach ($categories as $category) :
                        echo '<option value="' . esc_attr($category->term_id) . '" ' . (isset($_GET['event_category']) && $_GET['event_category'] == $category->term_id ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Table with Instant Search -->
    <table class="min-w-full table-auto border-collapse border border-gray-200 event-listing-tabale">
        <thead>
            <tr>
                <!-- Search box inside table header row -->
                <th colspan="4" class="px-4 py-2">
                    <input type="text" id="instant-search" class="w-full border px-4 py-2 rounded-md" placeholder="Search by Event Title" />
                </th>
            </tr>
            <tr>
                <th class="px-4 py-2 text-left font-semibold">Date</th>
                <th class="px-4 py-2 text-left font-semibold">Event</th>
                <th class="px-4 py-2 text-left font-semibold">Location</th>
                <th class="px-4 py-2 text-left font-semibold">Category</th>
            </tr>
        </thead>
        <tbody id="event-table-body">
            <!-- Events will be dynamically loaded here by AJAX -->
        </tbody>
    </table>

    <div id="pagination" class="pagination mt-4"></div>  <!-- For pagination -->

</div>

</div> <!-- End of main-event-wrapper -->




<style>

::root  {
    --e-global-color-primary: #5BC9DE;
}
.main-event-wrapper h1,
.main-event-wrapper h2,
.main-event-wrapper p {
    font-family: Raleway !important;
}
#pagination ul.page-numbers li span.current,
.event-listing-tabale a {
    color: var(--e-global-color-primary)
}
#pagination ul.page-numbers {
    display: flex;
    column-gap: 20px;
}
#event-filters label.block {
    padding-bottom: 10px;
}


/* responsive start */
	
@media (max-width: 480px) {

		 
}
	 
	 
/* responsive end */
	 
	 
 </style>





<?php
get_footer();  // Include the footer
?>

<script>
// AJAX function to fetch filtered events with pagination support
function fetchEvents(paged = 1) {
    const filters = {
        event_name: document.getElementById('event_name').value,
        event_location: document.getElementById('event_location').value,
        event_category: document.getElementById('event_category').value,
        search: document.getElementById('instant-search').value,
        paged: paged // Pass the current page for pagination
    };

    // Prepare the query parameters
    let url = '<?php echo admin_url('admin-ajax.php'); ?>';
    const params = new URLSearchParams(filters);
    params.append('action', 'filter_events');
    
    // AJAX Request
    fetch(url + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Populate table body with events
            document.getElementById('event-table-body').innerHTML = data.table;
            // Update pagination
            document.getElementById('pagination').innerHTML = data.pagination;
        });
}

// Add event listeners to trigger the AJAX on input change
document.getElementById('event_name').addEventListener('change', () => fetchEvents());
document.getElementById('event_location').addEventListener('change', () => fetchEvents());
document.getElementById('event_category').addEventListener('change', () => fetchEvents());
document.getElementById('instant-search').addEventListener('input', () => fetchEvents());

// Pagination button handler
document.getElementById('pagination').addEventListener('click', function(e) {
    if (e.target && e.target.nodeName === 'A') {
        e.preventDefault();
        const pageNumber = e.target.href.split('paged=')[1];
        fetchEvents(pageNumber);
    }
});

// Initially fetch the events when the page loads
document.addEventListener('DOMContentLoaded', () => fetchEvents());

</script>
