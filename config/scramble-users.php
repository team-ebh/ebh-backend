<?php

declare(strict_types=1);

use App\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Users API Configuration
     */
    'api_path' => 'v1/users',
    'api_domain' => null,
    'export_path' => 'docs/v1/users.json',

    'info' => [
        'version' => '1.0.0',
        'title' => 'Users API Documentation',
        'description' => <<<'HTML'
<!-- API Documentation Tables -->
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

    'ui' => [
        'title' => 'Users API Documentation',
        'theme' => 'light',
        'hide_try_it' => false,
        'logo' => '/images/Nizek Logo - Black.svg',
        'try_it_credentials_policy' => 'include',
    ],

    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
