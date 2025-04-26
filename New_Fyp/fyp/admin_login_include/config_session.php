<?php
    ini_set('session.use_only_cookies',1);
    ini_set('session.use_strict_mode',1);



    $options=[
        'cost'=>12
    ];

    session_start();

    if(!isset($_SESSION['generation_id'])){
        session_regenerate_id(true);
        $_SESSION['generation_id']=time();
    }else{
        $interval=30*60;

        if(time()-$_SESSION['generation_id']>=$interval){
        session_regenerate_id(true);
        $_SESSION['generation_id']=time();
        }

    }

  