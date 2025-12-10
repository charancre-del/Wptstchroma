import pandas as pd
import os

output_lines = []
output_lines.append("# Site Issues Report")
output_lines.append(f"Generated from: issues-68299-2025-12-09.xls")
output_lines.append(f"Date: 2025-12-09\n")

try:
    # Read all sheets from the Excel file
    xlsx = pd.ExcelFile(r'c:\Users\chara\Documents\wptheme\Wptstchroma\issues-68299-2025-12-09.xls', engine='openpyxl')
    
    output_lines.append(f"## Sheets Found: {len(xlsx.sheet_names)}\n")
    
    for i, sheet_name in enumerate(xlsx.sheet_names):
        try:
            df = pd.read_excel(xlsx, sheet_name=sheet_name)
            if df.empty or df.shape[0] == 0:
                continue
            
            # Check if it's a header row (contains 'issue_name' or similar)
            first_row = df.iloc[0].astype(str).str.lower()
            if 'issue_name' in first_row.values or 'label' in first_row.values:
                df.columns = df.iloc[0]
                df = df.iloc[1:]
            
            # Skip empty sheets
            if df.empty:
                continue
            
            output_lines.append(f"### {sheet_name}\n")
            
            # Focus on key columns if they exist
            key_cols = ['issue_name', 'label', 'description', 'affected_pages', 'severity_type', 'is_compliant']
            available_cols = [c for c in key_cols if c in df.columns]
            
            if available_cols:
                subset_df = df[available_cols].dropna(how='all')
                # Filter to non-compliant issues
                if 'is_compliant' in subset_df.columns:
                    non_compliant = subset_df[subset_df['is_compliant'].astype(str).str.lower() != 'true']
                    if not non_compliant.empty:
                        output_lines.append(f"**Non-Compliant Issues ({len(non_compliant)}):**\n")
                        output_lines.append(non_compliant.to_markdown(index=False))
                        output_lines.append("\n")
            else:
                # Just output the first 20 rows
                if len(df) > 0:
                    output_lines.append(df.head(20).to_markdown(index=False))
                    output_lines.append("\n")
                    
        except Exception as e:
            output_lines.append(f"Error reading sheet '{sheet_name}': {e}\n")

except Exception as e:
    output_lines.append(f"Error: {e}")

# Write to markdown file
output_path = r'c:\Users\chara\Documents\wptheme\Wptstchroma\SITE_ISSUES.md'
with open(output_path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(output_lines))

print(f"Written to: {output_path}")
print(f"Total lines: {len(output_lines)}")
