/**
 * One control per template variable type.
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	ColorIndicator,
	ColorPicker,
	ComboboxControl,
	Dropdown,
	RangeControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import MediaField from './MediaField';

const ColorField = ( { label, value, onChange } ) => (
	<BaseControl label={ label } __nextHasNoMarginBottom>
		<Dropdown
			popoverProps={ { placement: 'left-start' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="rsfv-studio-color"
					variant="secondary"
					onClick={ onToggle }
					aria-expanded={ isOpen }
				>
					<ColorIndicator colorValue={ value } />
					<span>{ value }</span>
				</Button>
			) }
			renderContent={ () => (
				<ColorPicker
					color={ value }
					enableAlpha={ false }
					onChange={ ( color ) => onChange( color ) }
				/>
			) }
		/>
	</BaseControl>
);

/**
 * Control for a variable.
 *
 * @param {Object}   props           Props.
 * @param {Object}   props.def       Variable definition.
 * @param {*}        props.value     Value.
 * @param {Object}   props.fonts     Fonts.
 * @param {boolean}  props.fromPost  Value comes from the post.
 * @param {boolean}  props.canRefill Post has a value this can go back to.
 * @param {Function} props.onRefill  Go back to the post value.
 * @param {Function} props.onChange  Change handler.
 * @return {JSX.Element} Control.
 */
const FieldControl = ( {
	def,
	value,
	fonts,
	isPro = true,
	fromPost,
	canRefill,
	onRefill,
	onChange,
} ) => {
	let help = null;
	if ( fromPost ) {
		help = __( 'Filled from this post.', 'rsfv' );
	} else if ( canRefill ) {
		help = (
			<Button variant="link" onClick={ onRefill }>
				{ __( 'Use the value from this post', 'rsfv' ) }
			</Button>
		);
	}

	const common = {
		label: def.label,
		help,
		__nextHasNoMarginBottom: true,
	};

	switch ( def.type ) {
		case 'text':
			return (
				<TextareaControl
					{ ...common }
					value={ value || '' }
					maxLength={ def.maxLength }
					onChange={ onChange }
					rows={ 3 }
				/>
			);
		case 'number':
			return (
				<RangeControl
					{ ...common }
					value={ Number( value ) }
					min={ def.min }
					max={ def.max }
					step={ def.step || 1 }
					onChange={ ( v ) => onChange( v ) }
					__next40pxDefaultSize
				/>
			);
		case 'color':
			return (
				<ColorField
					label={ def.label }
					value={ value }
					onChange={ onChange }
				/>
			);
		case 'boolean':
			return (
				<ToggleControl
					{ ...common }
					checked={ !! value }
					onChange={ onChange }
				/>
			);
		case 'enum':
			return (
				<SelectControl
					{ ...common }
					value={ value }
					options={ ( def.options || [] ).map( ( o ) =>
						'object' === typeof o ? o : { value: o, label: o }
					) }
					onChange={ onChange }
					__next40pxDefaultSize
				/>
			);
		case 'font': {
			const options = Object.keys( fonts ).map( ( family ) => ( {
				value: family,
				label: fonts[ family ].category
					? `${ fonts[ family ].label || family } (${
							fonts[ family ].category
					  })`
					: fonts[ family ].label || family,
			} ) );
			if ( ! isPro ) {
				options.push( {
					value: '',
					label: __( 'All Google Fonts (PRO)', 'rsfv' ),
					disabled: true,
				} );
			}
			// Long lists (PRO Google Fonts) get a searchable picker.
			if ( options.length > 20 ) {
				return (
					<ComboboxControl
						{ ...common }
						value={ value }
						options={ options }
						onChange={ ( next ) => next && onChange( next ) }
						__next40pxDefaultSize
					/>
				);
			}
			return (
				<SelectControl
					{ ...common }
					value={ value }
					options={ options }
					onChange={ onChange }
					__next40pxDefaultSize
				/>
			);
		}
		case 'image':
		case 'images':
			return (
				<MediaField
					label={ def.label }
					help={ help }
					value={ value }
					multiple={ 'images' === def.type }
					max={ def.max }
					onChange={ onChange }
				/>
			);
		case 'string':
		default:
			return (
				<TextControl
					{ ...common }
					value={ value || '' }
					maxLength={ def.maxLength }
					onChange={ onChange }
					__next40pxDefaultSize
				/>
			);
	}
};

export default FieldControl;
