// Import vendor dependencies
const { __ } = wp.i18n;

// Import store dependencies
import { useStoreValue } from '../../app/store';
import { goToStep } from '../../app/store/actions';

// Import components
import Button from '../button';
import Chevron from '../chevron';

// Import styles
import './style.scss';

const ContinueButton = () => {
	const [ { currentStep }, dispatch ] = useStoreValue();

	return (
		<Button onClick={ () => dispatch( goToStep( currentStep + 1 ) ) }>
			{ __( 'Continue', 'give' ) }
			<span className="give-obw-continue-button__icon">
				<Chevron />
			</span>
		</Button>
	);
};

export default ContinueButton;
