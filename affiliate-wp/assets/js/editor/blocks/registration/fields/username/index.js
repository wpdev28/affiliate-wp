/**
 * Affiliate registration username field Block.
 *
 * @since 2.8
 */

/**
 * Internal dependencies
 */
import edit from './edit';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { Icon } from '@wordpress/components';

const icon = <Icon
	icon={
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" fill="none" />
		</svg>
	}
/>

const name = 'affiliatewp/field-username';

const settings = {
	/* translators: block name */
	title: __('Username', 'affiliate-wp' ),
	category: 'affiliatewp',
	parent: ['affiliatewp/registration'],
	icon,
	attributes: {
		label: {
			type: 'string',
			default: __('Username', 'affiliate-wp' )
		},
		placeholder: {
			type: 'string',
		},
		required: {
			type: 'boolean',
			default: false,
		},
		type: {
			type: 'string',
		},
	},
	/* translators: block description */
	description: __('The affiliate\'s username.', 'affiliate-wp' ),
	keywords: [
		'affiliatewp',
		/* translators: block keyword */
		__('username', 'affiliatewp'),
		/* translators: block keyword */
		__('login', 'affiliatewp'),
		/* translators: block keyword */
		__('text', 'affiliatewp'),
	],
	supports: {
		html: false,
		lightBlockWrapper: true,
	},
	edit,
	save: () => null,
};

export { name, settings };
