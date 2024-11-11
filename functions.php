/* Server-Side (AJAX Handler Update)
In your functions.php, you should modify the AJAX handler to support paginated results properly. Here's the updated handler: */

function filter_events_ajax() {
    // Default query args
    $paged = isset($_GET['paged']) ? $_GET['paged'] : 1;

    $args = array(
        'post_type' => 'event',
        'posts_per_page' => 10,
        'paged' => $paged,
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'meta_key' => 'event_date',
        'meta_type' => 'DATE',
    );

    // Apply filters (Event name, Location, Category, and Search)
    if (isset($_GET['event_name']) && !empty($_GET['event_name'])) {
        $args['p'] = $_GET['event_name'];
    }

    if (isset($_GET['event_location']) && !empty($_GET['event_location'])) {
        $args['meta_query'] = array(
            array(
                'key' => 'event_location',
                'value' => sanitize_text_field($_GET['event_location']),
                'compare' => 'LIKE',
            ),
        );
    }

    if (isset($_GET['event_category']) && !empty($_GET['event_category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'event-category',
                'field' => 'id',
                'terms' => sanitize_text_field($_GET['event_category']),
                'operator' => 'IN',
            ),
        );
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $args['s'] = sanitize_text_field($_GET['search']);
    }

    // Execute the query
    $query = new WP_Query($args);

    // Check if there are posts
    if ($query->have_posts()) :
        $table = '';
        while ($query->have_posts()) : $query->the_post();
            $event_date = get_post_meta(get_the_ID(), 'event_date', true);
            $event_location = get_post_meta(get_the_ID(), 'event_location', true);
            $event_categories = wp_get_post_terms(get_the_ID(), 'event-category');
            $formatted_date = date('j.M.Y', strtotime($event_date));
            $categories = array_map(function($category) {
                return $category->name;
            }, $event_categories);

            $table .= '<tr>';
            $table .= '<td class="px-4 py-2 border-b border-gray-200">' . esc_html($formatted_date) . '</td>';
            $table .= '<td class="px-4 py-2 border-b border-gray-200"><a href="' . get_permalink() . '" class="text-blue-500 hover:underline">' . get_the_title() . '</a></td>';
            $table .= '<td class="px-4 py-2 border-b border-gray-200">' . esc_html($event_location) . '</td>';
            $table .= '<td class="px-4 py-2 border-b border-gray-200">' . implode(', ', $categories) . '</td>';
            $table .= '</tr>';
        endwhile;

        // Handle Pagination
        $pagination = paginate_links(array(
            'total' => $query->max_num_pages,
            'current' => max(1, $paged),
            'format' => '?paged=%#%',
            'type' => 'list',
        ));

        wp_reset_postdata();
    else :
        $table = '<tr><td colspan="4">No events found.</td></tr>';
        $pagination = '';
    endif;

    // Return table and pagination as JSON
    echo json_encode(array(
        'table' => $table,
        'pagination' => $pagination,
    ));
    die(); // End the AJAX request
}

add_action('wp_ajax_filter_events', 'filter_events_ajax');
add_action('wp_ajax_nopriv_filter_events', 'filter_events_ajax');
