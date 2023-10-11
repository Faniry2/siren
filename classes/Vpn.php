<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Vpn
 *
 * @author sleco
 */
class Vpn {
    
       /// fonctions utilitaires ///////
    function vpnConnect()
    {

        $cmd = "/usr/bin/nordvpn";
        $parts = array(
            'connect'
        );
        $args = '';
        foreach ($parts as $k => $part) {
            if (is_string($k)) {
                $args .= ' ' . escapeshellarg($k) . ' ' . escapeshellarg($part);
            } else {
                $args .= ' ' . escapeshellarg($part);
            }
        }
        $process_cmd = '"' . $cmd . '"' . ' ' . $args;
        $env = NULL;
        $options = array('bypass_shell' => true);
        $cwd = NULL;
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")  // stderr is a file to write to
        );
        $process = proc_open($process_cmd, $descriptorspec, $pipes, $cwd, $env, $options);
        if (is_resource($process)) {
            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            echo "VPN CONNECT OK\n";
        }


    }

    function vpnDisconnect()
    {
        $cmd = "/usr/bin/nordvpn";
        $parts = array(
            'disconnect'
        );
        $args = '';
        foreach ($parts as $k => $part) {
            if (is_string($k)) {
                $args .= ' ' . escapeshellarg($k) . ' ' . escapeshellarg($part);
            } else {
                $args .= ' ' . escapeshellarg($part);
            }
        }
        $process_cmd = '"' . $cmd . '"' . ' ' . $args;
        $env = NULL;
        $options = array('bypass_shell' => true);
        $cwd = NULL;
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")  // stderr is a file to write to
        );
        $process = proc_open($process_cmd, $descriptorspec, $pipes, $cwd, $env, $options);
        if (is_resource($process)) {
            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            echo "VPN DISCONNECT OK\n";
        }
    }
}
