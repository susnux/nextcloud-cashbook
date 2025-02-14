/*!
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: CC0-1.0
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
	{
		'main': resolve(join('src', 'main.ts')),
		'export': resolve(join('src', 'export.ts')),
	},
	{
		createEmptyCSSEntryPoints: true,
		config: {
			build: {
				cssCodeSplit: true,
			},
		},
	},
)
