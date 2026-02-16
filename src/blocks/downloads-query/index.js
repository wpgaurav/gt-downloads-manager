import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import TermSuggestControl from '../components/term-suggest-control';

registerBlockType( 'gtdm/downloads-query', {
	apiVersion: 3,
	title: __( 'GT Downloads Query', 'gt-downloads-manager' ),
	icon: 'download',
	category: 'widgets',
	attributes: {
		category: { type: 'string', default: '' },
		tag: { type: 'string', default: '' },
		search: { type: 'string', default: '' },
		sort: { type: 'string', default: 'newest' },
		perPage: { type: 'number', default: 12 },
		page: { type: 'number', default: 1 },
		layout: { type: 'string', default: 'grid' },
		filters: { type: 'boolean', default: true },
		image: { type: 'string', default: 'medium' },
	},
	edit: ( { attributes, setAttributes } ) => (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Query settings', 'gt-downloads-manager' ) }
					initialOpen
				>
					<TermSuggestControl
						label={ __( 'Category slug', 'gt-downloads-manager' ) }
						type="categories"
						value={ attributes.category }
						onChange={ ( value ) =>
							setAttributes( { category: value } )
						}
					/>
					<TermSuggestControl
						label={ __( 'Tag slug', 'gt-downloads-manager' ) }
						type="tags"
						value={ attributes.tag }
						onChange={ ( value ) =>
							setAttributes( { tag: value } )
						}
					/>
					<div style={ { marginBottom: '16px' } }>
						<TextControl
							label={ __(
								'Default search',
								'gt-downloads-manager'
							) }
							value={ attributes.search }
							onChange={ ( value ) =>
								setAttributes( { search: value } )
							}
						/>
					</div>
					<div style={ { marginBottom: '16px' } }>
						<SelectControl
							label={ __( 'Sort', 'gt-downloads-manager' ) }
							value={ attributes.sort }
							options={ [
								{
									label: __(
										'Newest first',
										'gt-downloads-manager'
									),
									value: 'newest',
								},
								{
									label: __(
										'Oldest first',
										'gt-downloads-manager'
									),
									value: 'oldest',
								},
								{
									label: __(
										'Most downloaded',
										'gt-downloads-manager'
									),
									value: 'popular',
								},
								{
									label: __(
										'Title A-Z',
										'gt-downloads-manager'
									),
									value: 'title_asc',
								},
								{
									label: __(
										'Title Z-A',
										'gt-downloads-manager'
									),
									value: 'title_desc',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { sort: value } )
							}
						/>
					</div>
					<div style={ { marginBottom: '16px' } }>
						<RangeControl
							label={ __(
								'Items per page',
								'gt-downloads-manager'
							) }
							value={ attributes.perPage }
							min={ 1 }
							max={ 50 }
							onChange={ ( value ) =>
								setAttributes( { perPage: value || 12 } )
							}
						/>
					</div>
					<div style={ { marginBottom: '16px' } }>
						<SelectControl
							label={ __( 'Layout', 'gt-downloads-manager' ) }
							value={ attributes.layout }
							options={ [
								{
									label: __( 'Grid', 'gt-downloads-manager' ),
									value: 'grid',
								},
								{
									label: __(
										'Table',
										'gt-downloads-manager'
									),
									value: 'table',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { layout: value } )
							}
						/>
					</div>
					<div style={ { marginTop: '4px' } }>
						<ToggleControl
							label={ __(
								'Show filters',
								'gt-downloads-manager'
							) }
							checked={ !! attributes.filters }
							onChange={ ( value ) =>
								setAttributes( { filters: !! value } )
							}
						/>
					</div>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="gtdm/downloads-query"
				attributes={ attributes }
			/>
		</Fragment>
	),
	save: () => null,
} );
