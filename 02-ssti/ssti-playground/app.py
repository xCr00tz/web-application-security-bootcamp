from flask import Flask, render_template_string, request


app = Flask(__name__, static_url_path='/static')

@app.route("/")
def home():
    username = request.args.get('username') or None

    header_part = '''
    <html>

        <head>
        <title>Server Side Template Injection (SSTI) Demo - Login Page</title>
        <style>
        body {
            background-image: url('{{url_for('static', filename='background.jpg')}}');
            height: 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        </style>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://getbootstrap.com/docs/5.2/examples/sign-in/signin.css" rel="stylesheet">
        </head>

        <body class="text-center">
    '''

    if username == None:
        body_part = '''
        <main class="form-signin w-100 m-auto">
          <form>
            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <div class="form-floating">
              <input type="text" class="form-control" name="username" placeholder="Username">
              <label for="floatingInput">Username</label>
            </div><br>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-muted">&copy; 2022</p>
          </form>
        </main>
        </body>
        </html>
        '''
    else:
        body_part = '''
        <main class="form-signin w-100 m-auto">
        <h2>Hi {0}</h2><br>
        Welcome to the vulnerable app!
        </main>
        </body>
        </html>
        '''.format(username)

    template = header_part + body_part
    
    return render_template_string(template)

if __name__ == "__main__":
    app.run(debug=False, host='0.0.0.0', port=666)

