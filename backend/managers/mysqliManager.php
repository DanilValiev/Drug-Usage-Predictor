<?php

class MysqliManager
{
    private $connect;

    function __construct()
    {
        $this->connect = new mysqli('localhost', 'user', 'pass', 'db');
		$this->connect->query('SET NAMES \'utf8\'');
    }
	
    //Выполняем запросы, парсим с них нужную инфу.
    public function query($queryText, $type)
    {
        switch ($type) 
        {
            case 'assoc':
				$data = mysqli_fetch_assoc($this->connect->query($queryText));
                break;
            case 'all':
                $data = mysqli_fetch_all($this->connect->query($queryText));
                break;
            case 'multi':
                $data = $this->connect->multi_query($queryText);
                break;
            default:
                $data = $this->connect->query($queryText);
                break;
        }
        //$this->connect->close();
        /*
        if(mysqli_error($this->connect) != NULL)
        {
            print_r('MYSQLI ОШИБКА: <b style="color : red;">'.mysqli_error($this->connect).'</b> <br />');
        }
        */
        
        return $data;
    }
}
