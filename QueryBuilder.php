<?php

require_once("Database.php");
use QueryContructor\Database;

class QueryContructor extends Database
{
    protected $select;
    protected $from;
    protected $joinClause;
    protected $whereClause;
    protected $groupByClause;
    protected $havingClause;
    protected $orderByClause;
    protected $distinct;
    protected $operation;
    protected $table;
    protected $columns;
    protected $values;
    protected $buildType;

    public function __construct($cfg_options)
    {
        parent::__construct($cfg_options);
        $this->initialize();
    }

    protected function initialize()
    {
        $this->select = '*';
        $this->from = '';
        $this->joinClause = '';
        $this->whereClause = '';
        $this->groupByClause = '';
        $this->havingClause = '';
        $this->orderByClause = '';
        $this->distinct = false;
        $this->buildType = '';
    }

    // OPERAÇÕES COM SELECT

    public function Select($select = '*')
    {
        $this->buildType = "SELECT";
        $this->select = $select;
        return $this;
    }

    public function From($from)
    {
        $this->from = $from;
        return $this;
    }

    public function Where($column, $operator, $value, $logicalOperator = 'AND')
    {
        $placeholder = ":$column";
        $condition = "$column $operator $placeholder";
    
       
        $this->whereClause .= ($this->whereClause ? " $logicalOperator " : 'WHERE ') . $condition;
    
        $this->bind($placeholder, $value);
    
       
        echo $this->whereClause;
    
        return $this;
    }
    

    public function In($column, array $values, $logicalOperator = 'AND')
    {
        $inClause = implode(', ', array_map([$this, 'quote'], $values));
        $this->whereClause .= ($this->whereClause ? " $logicalOperator " : '') . "$column IN ($inClause)";
        return $this;
    }

    public function Like($column, $value, $logicalOperator = 'AND')
    {
        $this->whereClause .= ($this->whereClause ? " $logicalOperator " : '') . "$column LIKE :$column";
        parent::bind(":$column", $value);
        return $this;
    }

    public function OrderBy($column, $order = 'ASC')
    {
        $this->orderByClause = "ORDER BY $column $order";
        return $this;
    }

    public function GroupBy($column)
    {
        $this->groupByClause = "GROUP BY $column";
        return $this;
    }

    public function Having($condition)
    {
        $this->havingClause = "HAVING $condition";
        return $this;
    }

    public function Between($column, $value1, $value2, $logicalOperator = 'AND')
    {
        $this->whereClause .= ($this->whereClause ? " $logicalOperator " : '') . "$column BETWEEN :{$column}_1 AND :{$column}_2";
        parent::bind(":{$column}_1", $value1);
        parent::bind(":{$column}_2", $value2);
        return $this;
    }

    public function Join($table, $condition, $type = 'INNER')
    {
        $this->joinClause .= "$type JOIN $table ON $condition ";
        return $this;
    }

    public function Distinct()
    {
        $this->distinct = true;
        return $this;
    }

    
    ///////////////

    //OPERAÇÕES INSERT
    
    public function Insert($table, $columns)
    {
        $this->from = $table;
        $this->select = '';
        $this->joinClause = '';
        $this->whereClause = '';
        $this->groupByClause = '';
        $this->havingClause = '';
        $this->orderByClause = '';
        $this->distinct = false;
    
    
        $this->whereClause .= "INSERT INTO $this->from ";
    
    
        if (!empty($columns)) {
            $this->whereClause .= '(' . implode(', ', $columns) . ')';
        }
    
        return $this;
    }
    
    public function Values(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(function ($column) {
            return ":$column";
        }, array_keys($data)));
    
        $this->whereClause .= " VALUES ($placeholders)";
    
        foreach ($data as $column => $value) {
            $placeholder = ":$column";
            $this->bind($placeholder, $value);
        }
    
        return $this;
    }
    

    public function BuildSelect()
    {
        $query = "SELECT " . ($this->distinct ? 'DISTINCT ' : '') . "$this->select FROM $this->from $this->joinClause";

        if (!empty($this->whereClause)) {
            $query .= " WHERE $this->whereClause";
        }

        $query .= " $this->groupByClause $this->havingClause $this->orderByClause";

        $this->initialize(); 
        return $query;
    }
}

