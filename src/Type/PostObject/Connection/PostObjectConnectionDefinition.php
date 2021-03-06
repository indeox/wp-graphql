<?php
namespace WPGraphQL\Type\PostObject\Connection;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Types;

/**
 * Class PostObjectConnectionDefinition
 *
 * @package WPGraphQL\Type\Comment\Connection
 * @since   0.0.5
 */
class PostObjectConnectionDefinition {

	/**
	 * Stores some date for the Relay connection for post objects
	 *
	 * @var array $connection
	 * @since  0.0.5
	 * @access private
	 */
	private static $connection;

	/**
	 * Stores the added_args for the connection (in addition to the standard Relay args)
	 *
	 * @var array $added_args
	 */
	protected static $added_args;

	/**
	 * Method that sets up the relay connection for post objects
	 *
	 * @param object $post_type_object
	 *
	 * @return mixed
	 * @since 0.0.5
	 *
	 * @return mixed
	 */
	public static function connection( $post_type_object ) {

		if ( null === self::$connection ) {
			self::$connection = [];
		}

		if ( empty( self::$connection[ $post_type_object->name ] ) ) :
			/**
			 * Setup the connectionDefinition
			 *
			 * @since 0.0.5
			 */
			$connection = Relay::connectionDefinitions( [
				'nodeType'         => Types::post_object( $post_type_object->name ),
				'name'             => ucfirst( $post_type_object->graphql_plural_name ),
				'connectionFields' => function() use ( $post_type_object ) {

					return [
						'postTypeInfo' => [
							'type'        => Types::post_type(),
							'description' => __( 'Information about the type of content being queried', 'wp-graphql' ),
							'resolve'     => function( $source, array $args, AppContext $context, ResolveInfo $info ) use ( $post_type_object ) {
								return $post_type_object;
							},
						],
						'nodes' => [
							'type' => Types::list_of( Types::post_object( $post_type_object->name ) ),
							'description' => __( 'The nodes of the connection, without the edges', 'wp-graphql' ),
							'resolve' => function( $source, $args, $context, $info ) {
								return ! empty( $source['nodes'] ) ? $source['nodes'] : [];
							},
						],
					];
				},
			] );

			/**
			 * Add the connection to the post_objects_connection object
			 *
			 * @since 0.0.5
			 */
			self::$connection[ $post_type_object->name ] = [
				'type'        => $connection['connectionType'],
				// Translators: the placeholder is the name of the post_type
				'description' => sprintf( __( 'A collection of %s objects', 'wp-graphql' ), $post_type_object->graphql_plural_name ),
				'args'        => array_merge( Relay::connectionArgs(), self::added_args() ),
				'resolve'     => function( $source, array $args, AppContext $context, ResolveInfo $info ) use ( $post_type_object ) {
					return DataSource::resolve_post_objects_connection( $source, $args, $context, $info, $post_type_object->name );
				},
			];
		endif;

		/**
		 * Return the connection from the post_objects_connection object
		 *
		 * @since 0.0.5
		 */
		return self::$connection[ $post_type_object->name ];

	}

	/**
	 * Returns the $args that should be added to the connection args
	 * @return array
	 */
	protected static function added_args() {

		if ( null === self::$added_args ) {

			self::$added_args = [
				'where' => [
					'name' => 'where',
					'description' => __( '', 'wp-graphql' ),
					'type' => Types::post_object_query_args(),
				],
			];

		}

		return self::$added_args;

	}

}
