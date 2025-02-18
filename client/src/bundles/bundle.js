/* eslint-disable-next-line
 import/no-webpack-loader-syntax,
 import/no-unresolved
 */
require('expose-loader?exposes=AnchorSelectorActions!state/anchorSelector/AnchorSelectorActions');

require('../legacy/CMSMain.AddForm');
require('../legacy/CMSMain.EditForm');
require('../legacy/CMSMain');
require('../legacy/CMSMain.Tree');
require('../legacy/RedirectorPage');
require('../legacy/SiteTreeURLSegmentField');

require('boot');
