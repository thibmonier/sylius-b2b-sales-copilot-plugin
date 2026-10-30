import { Application } from '@hotwired/stimulus';
import CopilotController from './controllers/copilot_controller';

const application = Application.start();
application.register('tmonier-copilot', CopilotController);
