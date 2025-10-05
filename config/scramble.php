<?php

declare(strict_types=1);

use App\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your api path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'docs',

    /*
     * Your api domain. By default, app domain is used. This is also a part of the default api routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'docs/v1.json',

    'info' => [
        /*
         * api version.
         */
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Description rendered on the home page of the api documentation (`/docs/api`).
         */
        'description' => <<<'HTML'
<!-- Second table: Headers -->
<table style="width:100%; border-collapse: collapse; font-family: Arial, sans-serif; background-color: #fff; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
  <caption style="font-size: 18px; font-weight: bold; padding: 10px; color: #fff; text-align: left;">
    Required Headers (Examples)
  </caption>
  <thead>
    <tr style="background-color: #4CAF50; color: white; text-transform: uppercase; font-size: 14px;">
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Header</th>
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Example Value</th>
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">os</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">ios | android</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Operating system used by the client
      </td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">language</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">ar | en</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Language code
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">build_number</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">123344</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Build number of the client app
      </td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">version</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">1.0.0</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Client version
      </td>
    </tr>
  </tbody>
</table>
<table style="width:100%; border-collapse: collapse; font-family: Arial, sans-serif; background-color: #fff; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
  <caption style="font-size: 18px; font-weight: bold; padding: 10px; color: #fff; text-align: left;">
    HTTP Status Codes and Their Descriptions
  </caption>
  <thead>
    <tr style="background-color: #4CAF50; color: white; text-transform: uppercase; font-size: 14px;">
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Status Code</th>
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Description</th>
      <th style="padding: 12px 15px; border: 1px solid #ddd;">Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">200</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">OK</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The request was successful, and the server returned the expected response.
      </td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">201</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Created</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The request was successful, and a new resource was created.
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">204</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">No Content</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The request was successful, but there is no response body.
      </td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">400</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Bad Request</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The server cannot process the request due to client error (e.g., invalid input).
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">401</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Unauthorized</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Authentication is required or has failed.
      </td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">403</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Forbidden</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        Client Authenticated but does not have permission to access the resource.
      </td>
    </tr>
      <tr style="background-color: #f9f9f9;">
      <td style="padding: 12px 15px; border: 1px solid #ddd;">406</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Not Acceptable</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The server could not produce a response matching the list of acceptable values defined in the request's.
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">422</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Unprocessable Entity</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        The server understands the request but cannot process it due to semantic errors.<br><br>
        <strong>Example:</strong>
        <pre style="background: #f8f8f8; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
{
  "data": null,
  "meta": {
    "message": "The name field is required. (and 2 more errors)",
    "errors": [
      {
        "field": "name",
        "errors": [
          "The name field is required."
        ]
      },
      {
        "field": "email",
        "errors": [
          "The email field is required."
        ]
      },
      {
        "field": "password",
        "errors": [
          "The password field is required."
        ]
      }
    ]
  }
}
        </pre>
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">500</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">Internal Server Error</td>
      <td style="padding: 12px 15px; border: 1px solid #ddd;">
        An error occurred on the server while processing the request.
      </td>
    </tr>
  </tbody>
</table>
HTML

    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Define the title of the documentation's website. App name is used when this config is `null`.
         */
        'title' => config('app.name'),

        /*
         * Define the theme of the documentation. Available options are `light` and `dark`.
         */
        'theme' => 'light',

        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * URL to an image that displays as a small square logo next to the title, above the table of contents.
         */
        'logo' => '/images/logo/light.svg',

        /*
         * Use to fetch the credential policy for the Try It feature. Options are: omit, include (default), and same-origin
         */
        'try_it_credentials_policy' => 'include',
    ],

    /*
     * The list of servers of the api. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
