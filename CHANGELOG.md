# Changelog

## 0.1.0 (2026-07-07)


### Features

* **actions:** Action contract + first architecture tests ([33edd5c](https://github.com/cosmicspork/kritee/commit/33edd5c4efe8f794e109caee889c28c538d33742))
* **actors:** add Actor abstraction and HTTP middleware wiring ([5557625](https://github.com/cosmicspork/kritee/commit/5557625568a5e90e620a3401dd69d0718e3dccea))
* **actors:** add Actor abstraction and HTTP middleware wiring ([4137989](https://github.com/cosmicspork/kritee/commit/41379893080c0922595a143e55fefc1bb49091a6))
* add domain models, migrations, and factories ([71ef4f8](https://github.com/cosmicspork/kritee/commit/71ef4f8d954e688d69ddfd8334d7b2fc20496a0f))
* add domain models, services, and action layer ([d23324c](https://github.com/cosmicspork/kritee/commit/d23324ce22527b998894ed719dcb728b0c6f1908))
* add domain services and action layer ([004bb48](https://github.com/cosmicspork/kritee/commit/004bb48605088925973614ec79dcb2bc098928b9))
* add make:action generator, idempotency, and actor wiring ([dc1d9ae](https://github.com/cosmicspork/kritee/commit/dc1d9ae6600069091d682e498bfc50f365c083e8))
* add make:action generator, idempotency, and actor wiring ([1718a44](https://github.com/cosmicspork/kritee/commit/1718a44125f8849c6346ca6437c350f41ce7a114))
* add product UI across the core domain ([8c38e53](https://github.com/cosmicspork/kritee/commit/8c38e5356211c073c848d6a8ac1074b6518ac77b))
* add product UI across the core domain ([3c8cc84](https://github.com/cosmicspork/kritee/commit/3c8cc84b0e7d533769dfc68c96ab9012aaf5af16))
* **auth:** add admin invite flow with user roles ([94aa08d](https://github.com/cosmicspork/kritee/commit/94aa08d6fffbc245a07f35259d3cfe29c0886052))
* **auth:** admin invite flow (invite-only onboarding) ([6dddbe7](https://github.com/cosmicspork/kritee/commit/6dddbe7426e3eb3cff180568c8509582068f836d))
* **auth:** gate public registration behind FORTIFY_REGISTRATION ([29e72d4](https://github.com/cosmicspork/kritee/commit/29e72d4c1bb23f83c8ac633ef21d593a8a02f612))
* **dashboard:** replace placeholder with today's ledger page ([16854e0](https://github.com/cosmicspork/kritee/commit/16854e0c611b501cdc5d8e394a26207b7ef835da))
* **dashboard:** replace placeholder with today's ledger page ([816860a](https://github.com/cosmicspork/kritee/commit/816860a21a75a4dfbf75eb3cc00e5213700ea13d))
* **documents:** add documents CRUD UI ([7fee079](https://github.com/cosmicspork/kritee/commit/7fee0799278d9882bc8348f930978e924be33f91))
* **documents:** add documents CRUD UI ([9f1c540](https://github.com/cosmicspork/kritee/commit/9f1c540bd684a64e15506bd26fdfa94f5b63bb75))
* **documents:** seed sample documents ([b748ee8](https://github.com/cosmicspork/kritee/commit/b748ee8fc705235dd17ff03caa5297c00dc9c99b))
* **invoices:** sweep overdue invoices on schedule as the system actor ([20605cc](https://github.com/cosmicspork/kritee/commit/20605cc71400a99796683b4b330147b7b9817757))
* **invoices:** sweep overdue invoices on schedule as the system actor ([614d0ca](https://github.com/cosmicspork/kritee/commit/614d0cac8dbca94d1e4fc9dbbc3625fd40a5d002))
* **ledger:** import JSONL expense ledger with content-hash dedup ([39e52e8](https://github.com/cosmicspork/kritee/commit/39e52e867bd5ad402a470cc2ef281399e4abac9d))
* **ledger:** import JSONL expense ledger with content-hash dedup ([a9977c2](https://github.com/cosmicspork/kritee/commit/a9977c2521ce28205ab288f65e4f6db02e54037b))
* **roadmap:** add public roadmap pages ([67c8dfe](https://github.com/cosmicspork/kritee/commit/67c8dfe66ba5c5232ab2848b19c42342c01ce0b3))
* **roadmap:** add public roadmap pages ([6ac62c6](https://github.com/cosmicspork/kritee/commit/6ac62c6bac7e2c22669c8d4a411114eec5317da9))
* **roadmap:** group public roadmap by status; format durations and money ([8690ec3](https://github.com/cosmicspork/kritee/commit/8690ec35a01cf9cfce4cecdcea6d57bfde26d096))
* **roadmap:** group public roadmap by status; format durations and money ([791ebbf](https://github.com/cosmicspork/kritee/commit/791ebbfee8fe4af41f5cb0e9570321da1e7800c4))
* **ui:** custom khata/irongall themes, ledger logomark, drop starter residue ([f304aa0](https://github.com/cosmicspork/kritee/commit/f304aa06fb4d6571458da61eae894aa15759ba54))
* **ui:** custom khata/irongall themes, ledger logomark, drop starter residue ([2923132](https://github.com/cosmicspork/kritee/commit/2923132714cc4208dc99bfe98bd62f5350027cda))


### Bug Fixes

* avoid restarting supervisord if already running in devcontainer ([a7ffe50](https://github.com/cosmicspork/kritee/commit/a7ffe50e203e6db4da624e7524a577474822839a))
* avoid restarting supervisord if already running in devcontainer ([6ee9426](https://github.com/cosmicspork/kritee/commit/6ee94260bf7f67b80655773abb0d3d8a47e3997d))
* **deps:** bump symfony/polyfill-intl-idn to 1.38.1 ([fdef8ab](https://github.com/cosmicspork/kritee/commit/fdef8ab0111644cd7a95e357bb87665da99f65db))
* **deps:** bump symfony/polyfill-intl-idn to 1.38.1 ([411b7d5](https://github.com/cosmicspork/kritee/commit/411b7d50befe4ef116bd2b41d07b5ec42106f715))
* **ui:** re-apply theme on livewire navigation ([27909c2](https://github.com/cosmicspork/kritee/commit/27909c2c500337327daa6b2cf4d32200e16b644b))
* **ui:** re-apply theme on livewire navigation ([d644932](https://github.com/cosmicspork/kritee/commit/d644932dc1ad0572baf061606118849d663cfe2d))

## Changelog
