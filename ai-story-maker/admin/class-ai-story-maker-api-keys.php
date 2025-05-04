<?php
/**
 * API Key validation helper for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author Hayan Mamoun
 * @license GPLv2 or later
 * @link https://github.com/hmamoun/ai-story-maker/wiki
 * @since 0.1.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_API_Keys
 *
 * Provides static methods to validate external API keys (OpenAI, Unsplash).
 *
 * @since 0.1.0
 */
class AISTMA_API_Keys {

    /**
     * Validates the Unsplash API key.
     *
     * @since 0.1.0
     *
     * @param string $api_key The Unsplash API key.
     * @return bool True if the key is valid, false otherwise.
     */
    public static function aistma_validate_aistma_unsplash_api_key( $api_key ) {
        $api_key = sanitize_text_field( wp_unslash( $api_key ) );

        // Check format (32-char alphanumeric).
        if ( ! preg_match( '/^[a-zA-Z0-9]{32}$/', $api_key ) ) {
            return false;
        }

        $url = 'https://api.unsplash.com/photos/random?client_id=' . urlencode( $api_key );
        $response = wp_remote_get( $url, [ 'timeout' => 5 ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }

    /**
     * Validates the OpenAI API key and optionally stores available model IDs in session.
     *
     * @since 0.1.0
     *
     * @param string $api_key The OpenAI API key.
     * @return bool True if the key is valid or empty (gracefully), false on failure.
     */
    public static function aistma_validate_aistma_openai_api_key( $api_key ) {
        $api_key = sanitize_text_field( wp_unslash( $api_key ) );

        if ( $api_key === '' ) {
            return true; // Optional field accepted
        }

        $response = wp_remote_get( 'https://api.openai.com/v1/models', [
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body  = wp_remote_retrieve_body( $response );
        $data  = json_decode( $body, true );
        $models = [];

        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            foreach ( $data['data'] as $model ) {
                if ( isset( $model['id'] ) ) {
                    $models[] = $model['id'];
                }
            }

            return true;
        }

        return false;
    }
}