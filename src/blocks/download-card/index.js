import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import DownloadSearchControl from '../components/download-search-control';

registerBlockType( 'gtdm/download-card', {
	apiVersion: 3,
	title: __( 'GT Download Card', 'gt-downloads-manager' ),
	icon: 'download',
	category: 'widgets',
	attributes: {
		id: { type: 'number', default: 0 },
		image: { type: 'string', default: 'medium' },
	},
	edit: ( { attributes, setAttributes } ) => (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Card settings', 'gt-downloads-manager' ) }
					initialOpen
				>
					<DownloadSearchControl
						selectedId={ attributes.id }
						onSelect={ ( value ) =>
							setAttributes( { id: Number( value ) || 0 } )
						}
					/>
					<SelectControl
						label={ __( 'Image size', 'gt-downloads-manager' ) }
						value={ attributes.image }
						options={ [
							{
								label: __(
									'Thumbnail',
									'gt-downloads-manager'
								),
								value: 'thumbnail',
							},
							{
								label: __( 'Medium', 'gt-downloads-manager' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'gt-downloads-manager' ),
								value: 'large',
							},
							{
								label: __( 'Full', 'gt-downloads-manager' ),
								value: 'full',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { image: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="gtdm/download-card"
				attributes={ attributes }
			/>
		</Fragment>
	),
	save: () => null,
} );
