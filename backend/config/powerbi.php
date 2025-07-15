<?php

return [
	'servicePrincipalToken' => 'https://login.microsoftonline.com/{tenant}/oauth2/token',
	'reportPagesList' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/reports/{reportId}/pages',
	'reportExport' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/reports/{reportId}/ExportTo',
	'checkImageStatus' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/reports/{reportId}/exports/{exportId}',
	'downloadExportedFile' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/reports/{reportId}/exports/{exportId}/file',
	'getWorkspaces' => 'https://api.powerbi.com/v1.0/myorg/groups',
	'getReports' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/reports',
	'getDashboards' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/dashboards',
	'getEmbeddedURL' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/{tipo}/{reportId}',
	'getEmbeddedToken' => 'https://api.powerbi.com/v1.0/myorg/groups/{workspaceId}/{tipo}/{reportId}/GenerateToken',
];
