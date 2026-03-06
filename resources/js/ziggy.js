// Import route function from ziggy-js npm package
import { route } from 'ziggy-js';

// Get Ziggy configuration from window (provided by @routes directive in blade template)
// The ziggy-js package automatically reads window.Ziggy if available

// Make route function available globally for legacy code
if (typeof window !== 'undefined') {
    window.route = route;
}

// Export for any other imports
export { route };
