/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLoggerBuilder } from "@nextcloud/logger";

export default getLoggerBuilder()
	.setApp('cashbook')
	.build()