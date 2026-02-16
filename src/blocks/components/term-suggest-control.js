import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner, TextControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const DEBOUNCE_MS = 300;
const MAX_SUGGESTIONS = 8;

export default function TermSuggestControl( { label, value, onChange, type } ) {
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		let isMounted = true;
		const timer = setTimeout( async () => {
			setIsLoading( true );

			try {
				const path = addQueryArgs( `/gtdm/v2/terms/${ type }`, {
					search: value || '',
					per_page: MAX_SUGGESTIONS,
				} );
				const response = await apiFetch( { path } );

				if ( isMounted ) {
					setSuggestions( Array.isArray( response ) ? response : [] );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setSuggestions( [] );
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
	}, [ type, value ] );

	return (
		<div
			className="gtdm-term-control"
			style={ {
				marginBottom: '16px',
			} }
		>
			<TextControl
				label={ label }
				value={ value }
				onChange={ onChange }
			/>
			{ isLoading && <Spinner /> }
			{ suggestions.length > 0 && (
				<div
					className="gtdm-term-suggestions"
					aria-label={ __( 'Suggestions', 'gt-downloads-manager' ) }
					style={ {
						marginTop: '10px',
						marginBottom: '6px',
						display: 'flex',
						flexWrap: 'wrap',
						gap: '6px',
					} }
				>
					{ suggestions.map( ( term ) => (
						<Button
							key={ term.slug }
							variant="secondary"
							isSmall
							onClick={ () => onChange( term.slug ) }
						>
							{ term.name }
						</Button>
					) ) }
				</div>
			) }
		</div>
	);
}
