<?php

namespace App\Models;

class Notes_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'notes';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $notes_table = $this->db->prefixTable('notes');
        $users_table = $this->db->prefixTable('users');
        $note_category_table = $this->db->prefixTable('note_category');

        $where = "";
        $or_where = "";
        
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $notes_table.id=$id";
            $or_where = " $notes_table.is_public=1 AND $notes_table.deleted=0 AND $notes_table.id=$id"; //check public note 
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $notes_table.project_id=$project_id"; 
            $or_where = " $notes_table.is_public=1 AND $notes_table.deleted=0 AND $notes_table.project_id=$project_id "; //include public notes in projects
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $notes_table.client_id=$client_id";
        }

        $category_id = $this->_get_clean_value($options, "category_id");
        if ($category_id) {
            $where .= " AND $notes_table.category_id=$category_id";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $notes_table.user_id=$user_id";
        }


        $created_by = $this->_get_clean_value($options, "created_by");
        if ($created_by) {
            $where .= " AND $notes_table.created_by=$created_by";
        }

        $label_id = $this->_get_clean_value($options, "label_id");
        if ($label_id) {
            $where .= " AND (FIND_IN_SET('$label_id', $notes_table.labels)) ";
        }

        $my_notes = $this->_get_clean_value($options, "my_notes");
        if ($my_notes) {
            $where .= " AND $notes_table.user_id=0 AND $notes_table.client_id=0 "; //don't include client's and team member's notes
        }

        $search = get_array_value($options, "search");
        if ($search) {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND $notes_table.title LIKE '%$search%' ESCAPE '!'";
        }

        if($or_where){
            $where.=" OR ($or_where)";
        }
        
        $select_labels_data_query = $this->get_labels_data_query();
        
        $sql = "SELECT $notes_table.*, CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user_name, $note_category_table.name AS category_name, $select_labels_data_query
        FROM $notes_table
        LEFT JOIN $users_table ON $users_table.id=$notes_table.created_by
        LEFT JOIN $note_category_table ON $note_category_table.id= $notes_table.category_id
        WHERE $notes_table.deleted=0 $where ORDER BY $notes_table.created_at DESC";
     
        return $this->db->query($sql);
    }

    function get_label_suggestions($user_id) {

        $user_id = $this->_get_clean_value(array("user_id" => $user_id), "user_id");

        $notes_table = $this->db->prefixTable('notes');
        $sql = "SELECT GROUP_CONCAT(labels) as label_groups
        FROM $notes_table
        WHERE $notes_table.deleted=0 AND $notes_table.created_by=$user_id";
        return $this->db->query($sql)->getRow()->label_groups;
    }

}
