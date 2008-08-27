from windmill.authoring import setup_module, teardown_module, enable_collector, WindmillTestClient
import windmill

def setup_module(module):
    client = WindmillTestClient(__name__)

    client.type(text=u'cms/tests/CMSMainTest.yml', id=u'fixture-file')
    client.click(id=u'start-session')

    client.waits.forPageLoad(timeout=u'20000')
    client.click(id=u'admin-link')
    client.waits.forPageLoad(timeout=u'20000')

    client.type(text=u'admin@example.com', id=u'MemberLoginForm_LoginForm_Email')
    client.type(text=u'ZXXlkwecxz2390232233', id=u'MemberLoginForm_LoginForm_Password')
    client.click(id=u'MemberLoginForm_LoginForm_action_dologin')
    client.waits.forPageLoad(timeout=u'300000')

def teardown_module(module):
    client = WindmillTestClient(__name__)
    
    client.open(url=windmill.settings['TEST_URL'], id=u'fixture-file')
    client.click(id=u'end-session')
    