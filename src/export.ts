/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp  } from "vue"
import { router } from "./router"
import { pinia } from "./store"

import CashbookPublicApp from "./CashbookPublicApp.vue"

const app = createApp(CashbookPublicApp)
app.use(router)
app.use(pinia)
app.mount('#content')