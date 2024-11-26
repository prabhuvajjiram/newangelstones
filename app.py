from flask import Flask, render_template, request, redirect, url_for, flash, jsonify
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
import pandas as pd
import os
from werkzeug.security import generate_password_hash, check_password_hash

app = Flask(__name__)
app.config['SECRET_KEY'] = 'your-secret-key-here'  # Change this to a secure secret key
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

# Simple user class for admin authentication
class User(UserMixin):
    def __init__(self, id):
        self.id = id

# Create a single admin user
admin_user = User(1)
admin_password_hash = generate_password_hash('admin')  # Change 'admin' to a secure password

@login_manager.user_loader
def load_user(user_id):
    return admin_user if int(user_id) == 1 else None

# Routes
@app.route('/')
def home():
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        password = request.form.get('password')
        if check_password_hash(admin_password_hash, password):
            login_user(admin_user)
            return redirect(url_for('admin_dashboard'))
        flash('Invalid password')
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))

@app.route('/admin')
@login_required
def admin_dashboard():
    try:
        # Read Excel file
        df = pd.read_excel('Cost_Calculator_unprot.xlsx')
        # Get column names for the form
        columns = df.columns.tolist()
        return render_template('admin.html', columns=columns)
    except Exception as e:
        flash(f'Error reading Excel file: {str(e)}')
        return render_template('admin.html', columns=[])

@app.route('/generate_quote', methods=['POST'])
@login_required
def generate_quote():
    try:
        data = request.get_json()
        df = pd.read_excel('Cost_Calculator_unprot.xlsx')
        # Process the quote based on the Excel data and form inputs
        # This is a placeholder - you'll need to implement the actual quote calculation logic
        return jsonify({'success': True, 'quote': 'Quote details will be generated here'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})

if __name__ == '__main__':
    app.run(debug=True)
