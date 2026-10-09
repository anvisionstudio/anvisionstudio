<?php
/**
 * Standard API response envelope.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

use WP_Error;
use WP_REST_Response;

/**
 * Builds {success,code,message,data,meta} responses.
 */
final class ApiResponse {

	/**
	 * @param array<string, mixed>|list<mixed>|null $data Payload.
	 * @param array<string, mixed>                  $meta Meta.
	 */
	public static function success(
		string $code,
		string $message,
		$data = null,
		array $meta = array(),
		int $status = 200
	): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => true,
				'code'    => $code,
				'message' => $message,
				'data'    => $data,
				'meta'    => (object) $meta,
			),
			$status
		);
	}

	/**
	 * @param array<string, mixed> $meta Meta.
	 */
	public static function error(
		string $code,
		string $message,
		int $status,
		array $meta = array()
	): WP_Error {
		return new WP_Error(
			$code,
			$message,
			array_merge(
				array(
					'status'  => $status,
					'success' => false,
					'code'    => $code,
					'message' => $message,
					'data'    => null,
					'meta'    => (object) $meta,
				),
				$meta
			)
		);
	}
}
