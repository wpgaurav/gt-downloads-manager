import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import TermSuggestControl from '../components/term-suggest-control';

registerBlockType( 'gtdm/download-filters', {
	apiVersion: 3,
	title: __( 'GT Download Filters', 'gt-downloads-manager' ),
	icon: 'filter',
	category: 'widgets',
	attributes: {
		category: { type: 'string', default: '' },
		tag: { type: 'string', default: '' },
		search: { type: 'string', default: '' },
		sort: { type: 'string', default: 'newest' },
	},
	edit: ( { attributes, setAttributes } ) => (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Default filter values',
						'gt-downloads-manager'
					) }
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
					<div style={ { marginBottom: '8px' } }>
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
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="gtdm/download-filters"
				attributes={ attributes }
			/>
		</Fragment>
	),
	save: () => null,
} );
