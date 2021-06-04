<?php

namespace Give\Form\LegacyConsumer;

use DOMDocument;
use Give\Framework\FieldsAPI\FieldCollection;
use Give\Framework\FieldsAPI\FormField\FieldTypes;

class Renderer {
	public function __construct( FieldCollection $fieldCollection ) {
		$this->document        = new DOMDocument( '1.0', 'utf-8' );
		$this->fieldCollection = $fieldCollection;
		$this->currentRoot     = $this->document;
	}

	/**
	 * Render the field collection
	 */
	public function render() {
		$this->fieldCollection->walk(
			function ( $field ) {
				// Determine the needs of the field based on the type
				$config = static::deriveConfigFromType( $field->getType() );

				if ( $field->getType() === FieldTypes::TYPE_SECTION ) {
					// If currently within a section, reset the current root to the document.
					if ( $this->currentRoot !== $this->document ) {
						$this->currentRoot = $this->document;
					}

					$this->currentRoot->appendChild(
						$section = $this->createElement(
							'fieldset',
							[],
							$this->createElement( 'legend', [], $field->getLabel() )
						)
					);

					// This sets the new root to the new section
					$this->currentRoot = $section;

					// Bail out and continue rendering the next fields, now within the section.
					return;
				}

				// Radio (groups) are special. They render multiple inputs.
				if ( $field->getType() === FieldTypes::TYPE_RADIO ) {
					$labelContent = $this->labelContent( $field );

					$input = $this->createElement(
						'fieldset',
						[],
						// The legend is for semantics, but not visuals
						$this->createElement( 'legend', [ 'class' => 'screen-reader-text' ], $labelContent ),
						// This is for visuals, but excluded from screen readers.
						$this->createElement(
							'div',
							[
								'class'       => 'give-label',
								'aria-hidden' => true,
							],
							$labelContent
						),
						// Add the radio inputs
						static::map(
							$field->getOptions(),
							function ( $option, $value ) use ( $config, $field ) {
								// TODO: figure out the selected option
								return $this->createElement(
									'label',
									[],
									$this->createElement(
										$config->elementType,
										[
											'type'  => $config->inputType,
											'name'  => $field->getName(),
											'value' => $value,
										]
									),
									$option
								);
							}
						)
					);
				} else {
					// The base input/textarea/select element
					$input = $this->createElement(
						$config->elementType,
						array_merge(
							$field->getAttributes(),
							[
								'type'     => $config->inputType,
								'name'     => $field->getName(),
								'id'       => "give-{$field->getName()}",
								'class'    => static::setClassNames(
									[
										'give-input' => true,
										'required'   => $field->isRequired(),
									]
								),
								'required' => $field->isRequired(),
								'readonly' => $field->isReadOnly(),
								'value'    => $field->getDefaultValue(),
							]
						),
						// @formatter:off
						$field->getType() === FieldTypes::TYPE_SELECT
						? static::map(
							$field->getOptions(),
							function ( $label, $value ) {
								return $this->createElement( 'option', compact( 'value' ), $label );
							}
						) : []
						// @formatter:on
					);
				}

				// Most fields which visually display will need to use the wrapper
				// @formatter:off
				$this->currentRoot->appendChild(
					$config->useWrapper
					// Render the input inside the wrapper
					? $this->createElement(
						'div',
						[
							// TODO: determine if the row width should be configurable. Previous FFM functionality says, yes.
							'class'           => 'form-row form-row-wide',
							'data-field-name' => $field->getName(),
							'data-field-type' => $field->getType(),
						],
						$config->useLabel
						? $field->getType() === FieldTypes::TYPE_CHECKBOX
							// Checkbox inputs should be wrapped inside their label (which shouldn’t have the regular label styles).
							? $this->createElement( 'label', [], $input, $this->labelContent( $field ) )
							// Otherwise, place the label before the input and reference it with `for`.
							: [
								$this->createElement(
									'label',
									[
										'for'   => $input->getAttribute( 'id' ),
										'class' => 'give-label',
									],
									$this->labelContent( $field )
								),
								$input,
							]
						// Render the input without the label
						: $input
					)
					// Render the input without the wrapper
					: $input
				);
				// @formatter:on
			}
		);

		// Render the DOM as HTML
		echo $this->document->saveHTML();
	}

	/**
	 * This can be spread as children in createElement
	 */
	private function labelContent( $field ) {
		$content = [ $field->getLabel() ];

		if ( $field->isRequired() ) {
			$content[] = ' '; // For spacing
			$content[] = $this->createElement(
				'span',
				[ 'class' => 'give-required-indicator' ],
				'*'
			);
		}

		if ( $helpText = $field->getHelpText() ) {
			$content[] = ' '; // For spacing
			$content[] = $this->createElement(
				'span',
				[
					'class'      => 'give-tooltip hint--top hint--medium hint--bounce',
					'aria-label' => $helpText,
					// TODO: Previously this also had a `rel` attribute set to `tooltip`. afaik that’s not a legit thing.
				],
				$this->createElement( 'i', [ 'class' => 'give-icon give-icon-question' ] )
			);
		}

		return $content;
	}

	/**
	 * A helper to make DOMDocument more declarative.
	 *
	 * @param $elementType
	 * @param array $attributes
	 * @param ...$children
	 *
	 * @return \DOMElement|false
	 */
	private function createElement( $elementType, $attributes = [], ...$children ) {
		$element = $this->document->createElement( $elementType );

		// Set non-empty attributes on the element
		// TODO: figure out a better way to handle boolean attributes
		static::map(
			$attributes,
			function ( $key, $value ) use ( $element ) {
				if ( ! empty( $value ) ) {
					$element->setAttribute( $key, $value );
				}
			}
		);

		// Append all children. Make strings text nodes.
		array_walk_recursive(
			$children,
			function ( $child ) use ( $element ) {
				$element->appendChild( is_string( $child ) ? $this->document->createTextNode( $child ) : $child );
			}
		);

		return $element;
	}

	/**
	 * A helper for concatenating class names
	 *
	 * @param $classNames
	 *
	 * @return string
	 */
	private static function setClassNames( $classNames ) {
		// TODO: make declarative?
		// TODO: make this handle more ways of specifying class names

		$classString = '';

		foreach ( $classNames as $className => $shouldSet ) {
			if ( $shouldSet ) {
				$classString .= empty( $classString ) ? $className : " $className";
			}
		}

		return $classString;
	}

	/**
	 * Map with keys and values
	 *
	 * @param $array
	 * @param $callback
	 *
	 * @return mixed
	 */
	private static function map( $array, $callback ) {
		return array_map( $callback, array_keys( $array ), array_values( $array ) );
	}

	/**
	 * Derive the render config from the field type
	 *
	 * @param $type
	 *
	 * @return object
	 */
	private static function deriveConfigFromType( $type ) {
		$configFromType = [
			FieldTypes::TYPE_HIDDEN      => [
				'useWrapper'  => false,
				'useLabel'    => false,
				'elementType' => 'input',
				'inputType'   => 'hidden',
			],
			FieldTypes::TYPE_TEXTAREA    => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'textarea',
				'inputType'   => null,
			],
			FieldTypes::TYPE_TEXT        => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'text',
			],
			FieldTypes::TYPE_EMAIL       => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'email',
			],
			FieldTypes::TYPE_PHONE       => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'tel',
			],
			FieldTypes::TYPE_URL         => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'text',
			],
			FieldTypes::TYPE_DATE        => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'text',
			],
			FieldTypes::TYPE_CHECKBOX    => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'checkbox',
			],
			FieldTypes::TYPE_SELECT      => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'select',
				'inputType'   => 'option',
			],
			FieldTypes::TYPE_MULTISELECT => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'select',
				'inputType'   => 'option',
			],
			FieldTypes::TYPE_RADIO       => [
				'useWrapper'  => true,
				'useLabel'    => false,
				'elementType' => 'input',
				'inputType'   => 'radio',
			],
			FieldTypes::TYPE_FILE        => [
				'useWrapper'  => true,
				'useLabel'    => true,
				'elementType' => 'input',
				'inputType'   => 'text',
			],
		][ $type ];

		return (object) $configFromType;
	}
}