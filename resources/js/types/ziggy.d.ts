/**
 * Type definitions for Ziggy - Laravel route helper
 * These types provide TypeScript support for the route() function
 * from the ziggy-js npm package.
 *
 * Usage: import { route } from 'ziggy-js';
 */

 /**
  * Route parameter types - can be string, number, or object with key-value pairs
  */
 export type RouteParams = string | number | boolean | null | undefined | Record<string, string | number | boolean | null | undefined>;

 /**
  * Query parameters for the route
  */
 export type QueryParams = Record<string, string | number | boolean | null | undefined>;

 /**
  * Configuration object for Ziggy
  */
 export interface Config {
     url: string;
     port: number | null;
     default: {
         locale: string;
     };
     previous: string | null;
     schemes: Record<string, string>;
     fallback: {
         domain: string | null;
     };
 }

 /**
  * Route definition from Ziggy's internal route collection
  */
 export interface Route {
     uri: string;
     methods: string[];
     domain: string | null;
     name: string;
     action: Record<string, unknown>;
     wheres: Record<string, string>;
 }

 /**
  * Collection of all defined routes
  */
 export interface RouteCollection {
     [key: string]: Route;
 }

 /**
  * Main route function signature
  * @param name - The route name (e.g., 'inventory.suppliers.create')
  * @param params - Optional route parameters (e.g., { id: 1 } or 1)
  * @param absolute - Whether to return an absolute URL (default: true)
  * @returns The generated URL string
  */
 export function route(
     name: string,
     params?: RouteParams | RouteParams[],
     absolute?: boolean
 ): string;

 /**
  * Generate a URL with query parameters
  * @param name - The route name
  * @param params - Route parameters
  * @param query - Query parameters to append
  * @param absolute - Whether to return absolute URL
  */
 export function route(
     name: string,
     params?: RouteParams | RouteParams[],
     query?: QueryParams,
     absolute?: boolean
 ): string;

 /**
  * Check if a route exists
  * @param name - The route name to check
  */
 export function hasRoute(name: string): boolean;

 /**
  * Get the current route name
  */
 export function current(): string | null;

 /**
  * Get the current route parameters
  */
 export function currentParams(): Record<string, string>;

 /**
  * Get the current route's full URL
  * @param absolute - Whether to return absolute URL
  */
 export function currentUrl(absolute?: boolean): string;

 /**
  * Get the Ziggy configuration
  */
 export function config(): Config;

 /**
  * Get all defined routes
  */
 export function routes(): RouteCollection;

 /**
  * Get a specific route definition
  * @param name - The route name
  */
 export function getRoute(name: string): Route | undefined;

 /**
  * Set the base URL for generated URLs
  * @param url - The base URL
  */
 export function setBaseUrl(url: string): void;

 /**
  * Enable or disable absolute URL generation
  * @param enabled - Whether to generate absolute URLs
  */
 export function setAbsolute(enabled: boolean): void;
