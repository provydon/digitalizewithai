import Api from './Api'
import DashboardController from './DashboardController'
import DataViewController from './DataViewController'
import DataTableRowsController from './DataTableRowsController'
import Settings from './Settings'

const Controllers = {
    Api: Object.assign(Api, Api),
    DashboardController: Object.assign(DashboardController, DashboardController),
    DataViewController: Object.assign(DataViewController, DataViewController),
    DataTableRowsController: Object.assign(DataTableRowsController, DataTableRowsController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers