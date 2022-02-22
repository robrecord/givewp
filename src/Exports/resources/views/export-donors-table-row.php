<tr class="give-export-donors">
    <td scope="row" class="row-title">
        <h3>
            <span><?php esc_html_e( 'Export Donors', 'give' ); ?></span>
        </h3>
        <p><?php esc_html_e( 'Download a CSV of donors.', 'give' ); ?></p>
    </td>
    <td>
        <form method="post" id="give_donors_export" class="give-export-form">

            <?php
            echo Give()->html->date_field(
                [
                    'id'           => 'give_donors_export_donation_start_date',
                    'name'         => 'start_date',
                    'placeholder'  => esc_attr__( 'Start Date', 'give' ),
                    'autocomplete' => 'off',
                ]
            );

            echo Give()->html->date_field(
                [
                    'id'           => 'give_donors_export_donation_end_date',
                    'name'         => 'end_date',
                    'placeholder'  => esc_attr__( 'End Date', 'give' ),
                    'autocomplete' => 'off',
                ]
            );
            ?>

            <p id="give_donors_export_donation_search_by" style="display: none">Search by:
                    <input type=radio id="give_donors_export_donation_search_by_donation" name="search_by" value="donation" checked/>
                    <label>Donation date</label>
                    <input type=radio id="give_donors_export_donation_search_by_donor" name="search_by" value="donor"/>
                    <label>Donor creation date</label>
            </p>

            <?php
            echo Give()->html->forms_dropdown(
                [
                    'name'   => 'forms',
                    'id'     => 'give_donor_export_form',
                    'chosen' => true,
                    'class'  => 'give-width-25em',
                ]
            );
            ?>
            <br>
            <input type="submit" value="<?php esc_attr_e( 'Generate CSV', 'give' ); ?>" class="button-secondary"/>

            <div id="export-donor-options-wrap" class="give-clearfix">
                <p><?php esc_html_e( 'Export Columns:', 'give' ); ?></p>
                <ul id="give-export-option-ul">
                    <?php
                    $donor_export_columns = give_export_donors_get_default_columns();

                    foreach ( $donor_export_columns as $column_name => $column_label ) {
                        ?>
                        <li>
                            <label for="give-export-<?php echo esc_attr( $column_name ); ?>">
                                <input
                                    type="checkbox"
                                    checked
                                    name="give_export_columns[<?php echo esc_attr( $column_name ); ?>]"
                                    id="give-export-<?php echo esc_attr( $column_name ); ?>"
                                />
                                <?php echo esc_attr( $column_label ); ?>
                            </label>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
            </div>

            <?php wp_nonce_field( 'give_ajax_export', 'give_ajax_export' ); ?>
            <input type="hidden" name="give-export-class" value="Give_Donors_Export"/>
            <input type="hidden" name="give_export_option[query_id]" value="<?php echo uniqid( 'give_' ); ?>"/>
        </form>
    </td>
</tr>
