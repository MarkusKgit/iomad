<?php

//require_once(dirname(__FILE__) . '/../../../local/iomad/lib/blockpage.php');
require_once(dirname(__FILE__) . '/../../../local/course_selector/lib.php');

/**
 * Selector for any course
 */
class nonshopcourse_selector extends course_selector_base {
    const MAX_COURSES_PER_PAGE = 100;

    public function __construct($name, $options) {
        $this->selectedid  = $options['selectedid'];

        parent::__construct($name, $options);
    }

    /**
     * Any courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $DB;
        //by default wherecondition retrieves all courses except the deleted, not confirmed and guest
        list($wherecondition, $params) = $this->search_sql($search, 'c');

        $fields      = 'SELECT ' . $this->required_fields_sql('c').',c.shortname';
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {course} c
                WHERE 
                    (
                        (SELECT id FROM {course_shopsettings} css WHERE css.courseid = c.id AND css.id = $this->selectedid)
                        OR
                        NOT EXISTS (SELECT css.id FROM {course_shopsettings} css WHERE css.courseid = c.id )
                    )
                    AND c.id!=1 AND $wherecondition";
                  
        $order = ' ORDER BY c.sortorder, c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > self::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);
        
        // add the shortname to the course identifier
        foreach ($availablecourses as $key=>$availablecourse ) {
                $availablecourses[$key]->fullname = $availablecourse->fullname.' ('.$availablecourse->shortname.')';
        }

        if (empty($availablecourses)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('coursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('courses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}
