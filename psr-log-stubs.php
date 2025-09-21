<?php
/**
 * Minimal stubs for PSR-3 logger interfaces required by mPDF.
 */

namespace Psr\Log;

if ( ! interface_exists( __NAMESPACE__ . '\\LoggerInterface', false ) ) {
    interface LoggerInterface {
        public function emergency( $message, array $context = array() );
        public function alert( $message, array $context = array() );
        public function critical( $message, array $context = array() );
        public function error( $message, array $context = array() );
        public function warning( $message, array $context = array() );
        public function notice( $message, array $context = array() );
        public function info( $message, array $context = array() );
        public function debug( $message, array $context = array() );
        public function log( $level, $message, array $context = array() );
    }
}

if ( ! interface_exists( __NAMESPACE__ . '\\LoggerAwareInterface', false ) ) {
    interface LoggerAwareInterface {
        public function setLogger( LoggerInterface $logger );
    }
}

if ( ! class_exists( __NAMESPACE__ . '\\NullLogger', false ) ) {
    class NullLogger implements LoggerInterface {
        public function emergency( $message, array $context = array() ) {}
        public function alert( $message, array $context = array() ) {}
        public function critical( $message, array $context = array() ) {}
        public function error( $message, array $context = array() ) {}
        public function warning( $message, array $context = array() ) {}
        public function notice( $message, array $context = array() ) {}
        public function info( $message, array $context = array() ) {}
        public function debug( $message, array $context = array() ) {}
        public function log( $level, $message, array $context = array() ) {}
    }
}

