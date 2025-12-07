<?php
class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message ?? "$field is required";
        }
        return $this;
    }
    
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "$field must be a valid email";
        }
        return $this;
    }
    
    public function min($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = $message ?? "$field must be at least $length characters";
        }
        return $this;
    }
    
    public function max($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = $message ?? "$field must not exceed $length characters";
        }
        return $this;
    }
    
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field][] = $message ?? "$field must be one of: " . implode(', ', $values);
        }
        return $this;
    }
    
    public function unique($field, $table, $column = null, $excludeId = null, $message = null) {
        if (!isset($this->data[$field])) return $this;
        
        $column = $column ?? $field;
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $db->prepare($sql);
        
        if ($excludeId !== null) {
            $stmt->bind_param("ss", $this->data[$field], $excludeId);
        } else {
            $stmt->bind_param("s", $this->data[$field]);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $this->errors[$field][] = $message ?? "$field already exists";
        }
        
        return $this;
    }
    
    public function fails() {
        return !empty($this->errors);
    }
    
    public function errors() {
        return $this->errors;
    }
    
    public static function make($data) {
        return new self($data);
    }
}