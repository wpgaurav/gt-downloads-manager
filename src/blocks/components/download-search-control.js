import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner, TextControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const DEBOUNCE_MS = 300;
const MAX_RESULTS = 8;

export default function DownloadSearchControl( { selectedId, onSelect } ) {
	const [ query, setQuery ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ selectedTitle, setSelectedTitle ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		let isMounted = true;

		if ( ! selectedId ) {
			setSelectedTitle( '' );
			return () => {
				isMounted = false;
			};
		}

		const loadSelected = async () => {
			try {
				const response = await apiFetch( {
					path: `/gtdm/v2/downloads/${ selectedId }`,
				} );

				if ( isMounted && response && response.title ) {
					setSelectedTitle( response.title );
					setQuery( response.title );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setSelectedTitle( '' );
				}
			}
		};

		loadSelected();

		return () => {
			isMounted = false;
		};
	}, [ selectedId ] );

	useEffect( () => {
		let isMounted = true;
		const timer = setTimeout( async () => {
			setIsLoading( true );

			try {
				const path = addQueryArgs( '/gtdm/v2/downloads/search', {
					search: query || '',
					per_page: MAX_RESULTS,
				} );
				const response = await apiFetch( { path } );

				if ( isMounted ) {
					setResults( Array.isArray( response ) ? response : [] );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setResults( [] );
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		}, DEBOUNCE_MS );

		return () => {
			isMounted = false;
			clearTimeout( timer );
		};
	}, [ query ] );

	return (
		<div className="gtdm-download-search-control">
			<TextControl
				label={ __( 'Download name', 'gt-downloads-manager' ) }
				value={ query }
				help={ __(
					'Leave empty to use the latest published download.',
					'gt-downloads-manager'
				) }
				onChange={ setQuery }
			/>

			<div
				style={ {
					marginTop: '8px',
					display: 'flex',
					gap: '8px',
					alignItems: 'center',
					flexWrap: 'wrap',
				} }
			>
				<Button
					variant="tertiary"
					onClick={ () => {
						onSelect( 0 );
						setSelectedTitle( '' );
						setQuery( '' );
					} }
				>
					{ __( 'Use latest published', 'gt-downloads-manager' ) }
				</Button>
				{ selectedId > 0 && (
					<span>
						{ __( 'Selected:', 'gt-downloads-manager' ) }{ ' ' }
						{ selectedTitle ||
							__( 'Loading…', 'gt-downloads-manager' ) }
					</span>
				) }
			</div>

			{ isLoading && <Spinner /> }
			{ results.length > 0 && (
				<div
					className="gtdm-download-suggestions"
					aria-label={ __(
						'Download suggestions',
						'gt-downloads-manager'
					) }
					style={ {
						marginTop: '8px',
						display: 'flex',
						flexWrap: 'wrap',
						gap: '6px',
					} }
				>
					{ results.map( ( item ) => (
						<Button
							key={ item.id }
							variant={
								Number( selectedId ) === Number( item.id )
									? 'primary'
									: 'secondary'
							}
							isSmall
							onClick={ () => {
								onSelect( Number( item.id ) );
								setSelectedTitle( item.title );
								setQuery( item.title );
							} }
						>
							{ item.title }
						</Button>
					) ) }
				</div>
			) }
		</div>
	);
}
